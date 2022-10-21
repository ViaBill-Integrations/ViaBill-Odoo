# -*- coding: utf-8 -*-
# Part of Odoo. See LICENSE file for full copyright and licensing details.

import logging
import pprint
import time
from requests import Response
import requests

from werkzeug import urls

from odoo import _, models
from odoo.exceptions import ValidationError

from odoo.addons.payment_viabill.const import SUPPORTED_LOCALES
from odoo.addons.payment_viabill.controllers.main import ViabillController

_logger = logging.getLogger(__name__)


class PaymentTransaction(models.Model):
    _inherit = 'payment.transaction'

    def _get_specific_rendering_values(self, processing_values):
        """ Override of payment to return Viabill-specific rendering values.

               Note: self.ensure_one() from `_get_processing_values`

               :param dict processing_values: The generic and specific processing values of the transaction
               :return: The dict of acquirer-specific rendering values
               :rtype: dict
               """

        res = super()._get_specific_rendering_values(processing_values)
        if self.provider != 'viabill':
            return res

        payload = self._viabill_prepare_payment_request_payload()
        response: Response = self.acquirer_id._viabill_make_request('?controller=checkout', data=payload)

        try:
            response.raise_for_status()
        except requests.exceptions.RequestException:
            self.update({"state_message": "Viabill: " + _("Could not capture manually: %s, error : %s", self.reference,
                                                          response.json().get('error'))})
            raise ValidationError("Could not establish the connection to ViaBill Server.")

        payment_data = response.json()
        # The acquirer reference is set now to allow fetching the payment status after redirection
        self.acquirer_reference = payment_data.get('id')

        # Extract the checkout URL from the payment data and add it with its query parameters to the
        # rendering values. Passing the query parameters separately is necessary to prevent them
        # from being stripped off when redirecting the user to the checkout URL, which can happen
        # when only one payment method is enabled on Mollie and query parameters are provided.
        _logger.info("sending '?controller=checkout' request for link creation:\n%s", pprint.pformat(payment_data))
        self.update({'callback_res_id':123})
        checkout_url = payment_data['url']
        parsed_url = urls.url_parse(checkout_url)
        url_params = urls.url_decode(parsed_url.query)
        return {'api_url': checkout_url, 'url_params': url_params}

    def _viabill_prepare_payment_request_payload(self):
        """ Create the payload for the payment request based on the transaction values.

        :return: The request payload
        :rtype: dict
        """
        user_lang = self.env.context.get('lang')
        base_url = self.acquirer_id.get_base_url()
        redirect_url = urls.url_join(base_url, ViabillController._return_url)
        webhook_url = urls.url_join(base_url, ViabillController._notify_url)
        capture = False
        if not self.acquirer_id.capture_manually:
            capture = True

        test = False
        if self.acquirer_id.state == 'test':
            test = True

        return {
            'description': self.reference,
            'amount': {
                'currency': self.currency_id.name,
                'value': f"{self.amount:.2f}",
            },
            'locale': user_lang if user_lang in SUPPORTED_LOCALES else 'en_US',
            'capture': capture,
            # Since Viabill does not provide the transaction reference when returning from
            # redirection, we include it in the redirect URL to be able to match the transaction.
            'redirectUrl': f'{redirect_url}?reference={self.reference}',
            'webhookUrl': f'{webhook_url}?reference={self.reference}',
            'base_url': base_url,
            'test': test
        }

    def _get_tx_from_feedback_data(self, provider, data):
        """ Override of payment to find the transaction based on Viabill data.

        :param str provider: The provider of the acquirer that handled the transaction
        :param dict data: The feedback data sent by the provider
        :return: The transaction if found
        :rtype: recordset of `payment.transaction`
        :raise: ValidationError if the data match no transaction
        """
        tx = super()._get_tx_from_feedback_data(provider, data)
        if provider != 'viabill':
            return tx

        tx = self.search([('reference', '=', data.get('reference')), ('provider', '=', 'viabill')])
        if not tx:
            raise ValidationError(
                "Viabill: " + _("No transaction found matching reference %s.", data.get('ref'))
            )
        return tx

    def _process_feedback_data(self, data):
        """ Override of payment to process the transaction based on Viabill data.
                        _logger.info("Received data with invalid payment status: %s", payment_status)
        Note: self.ensure_one()

        :param dict data: The feedback data sent by the provider
        :return: None
        """

        super()._process_feedback_data(data)
        if self.provider != 'viabill':
            return

        payload = {"transaction": data.get("reference")}
        time.sleep(3)
        response: Response = self.acquirer_id._viabill_make_request(
            '?controller=status', data=payload, method="GET")


        try:
            response.raise_for_status()
        except requests.exceptions.RequestException:
            self.update({"state_message": "Viabill: " + _("Could not capture manually: %s, error : %s", self.reference,
                                                          response.json().get('error'))})
            raise ValidationError("Could not establish the connection to ViaBill Server.")

        payment_data = response.json()

        payment_status = payment_data.get('status')

        if payment_status in ['NEW', 'NONE']:
            self._set_pending()
        elif payment_status == 'APPROVED':
            self._set_authorized('authorized')
        elif payment_status == 'CAPTURED':
            self._set_done()
        elif payment_status in ['CANCELLED', 'REJECTED', 'REFUNDED']:
            self._set_canceled("Viabill: " + _("Canceled payment with status: %s", payment_status))
        else:
            _logger.info("Received data with invalid payment status: %s", payment_status)
            self._set_error(
                "Viabill: " + _("Received data with invalid payment status: %s", payment_status)
            )

    def _send_capture_request(self):
        """ Override of payment to send a capture request to Authorize.

        Note: self.ensure_one()

        :return: None
        """

        super()._send_capture_request()
        if self.provider != 'viabill':
            return

        test = False
        if self.acquirer_id.state == 'test':
            test = True

        payload = {
            "transaction": self.reference,
            "amount": self.amount,
            "currency": self.currency_id.name,
            "test": test
        }

        response: Response = self.acquirer_id._viabill_make_request(
            '?controller=capture', data=payload)

        _logger.info("Response Status Code : %s", response.status_code)

        try:
            response.raise_for_status()
        except requests.exceptions.RequestException:
            raise ValidationError("Viabill Message : " + response.json().get('error'))

        try:
            capture = response.json()
        except BaseException as e:
            raise ValidationError("Could not establish the connection to ViaBill Server.")

        if capture.get('status'):
            self._set_done('Manually Captured')
        else:
            _logger.info("Could not capture manually: %s", self.reference)
            self._set_error(
                "Viabill: " + _("Could not capture manually: %s, error : %s", self.reference,
                                capture.get('error'))
            )

    def _send_void_request(self):
        """ Request the provider of the acquirer handling the transaction to void it.

        For an acquirer to support authorization, it must override this method and request the
        transaction to be voided to its provider.

        Note: self.ensure_one()

        :return: None
        """
        self.ensure_one()

        super()._send_void_request()
        if self.provider != 'viabill':
            return

        test = False
        if self.acquirer_id.state == 'test':
            test = True

        payload = {
            "transaction": self.reference,
            "test": test,
        }

        response: Response = self.acquirer_id._viabill_make_request(
            '?controller=void', data=payload)

        try:
            response.raise_for_status()
        except requests.exceptions.RequestException:
            self.update({"state_message": "Viabill: " + _("Could not capture manually: %s, error : %s", self.reference,
                                                          response.json().get('error'))})
            raise ValidationError("Could not establish the connection to ViaBill Server.")

        void = response.json()

        if void.get('status'):
            self._set_canceled('Manually Cancelled')
        else:
            _logger.info("Could not capture manually: %s", self.reference)
            self._set_error(
                "Viabill: " + _("Could not capture manually: %s", self.reference)
            )

    def _create_refund_transaction(self, amount_to_refund=None, **custom_create_values):
        """ Create a new transaction with operation 'refund' and link it to the current transaction.

        :param float amount_to_refund: The strictly positive amount to refund, in the same currency
                                       as the source transaction
        :return: The refund transaction
        :rtype: recordset of `payment.transaction`
        """
        self.ensure_one()

        test = False
        if self.acquirer_id.state == 'test':
            test = True

        payload = {
            "transaction": self.reference,
            'amount': amount_to_refund or self.amount,
            'currency_id': self.currency_id.name,
            "test": test,
        }

        response: Response = self.acquirer_id._viabill_make_request(
            '?controller=refund', data=payload)

        try:
            response.raise_for_status()
        except requests.exceptions.RequestException:
            self.update({"state_message": "Viabill: " + _("Could not capture manually: %s, error : %s", self.reference,
                                                          response.json().get('error'))})
            raise ValidationError("Could not establish the connection to ViaBill Server.")

        refund = response.json()

        # return self.create({
        #     'acquirer_id': self.acquirer_id.id,
        #     'reference': self._compute_reference(self.provider, prefix=f'R-{self.reference}'),
        #     'amount': -(amount_to_refund or self.amount),
        #     'currency_id': self.currency_id.id,
        #     'token_id': self.token_id.id,
        #     'operation': 'refund',
        #     'source_transaction_id': self.id,
        #     'partner_id': self.partner_id.id,
        #     **custom_create_values,
        # })

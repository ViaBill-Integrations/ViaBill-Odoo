# -*- coding: utf-8 -*-
# Part of Odoo. See LICENSE file for full copyright and licensing details.

import logging
import requests
from werkzeug import urls
import pprint

from odoo import _, api, fields, models, service
from odoo.exceptions import ValidationError


_logger = logging.getLogger(__name__)

from .const import SUPPORTED_CURRENCIES

class PaymentAcquirer(models.Model):
    _inherit = 'payment.acquirer'


    provider = fields.Selection(
        selection_add=[('viabill', 'Viabill')], ondelete={'viabill': 'set default'}
    )

    viabill_base_url = fields.Char(
        string="Viabill Base Url",
        help="When Odoo sends request to viabill, this will be the base url.",
        required_if_provider="viabill", groups="base.group_system"
    )

    def _get_compatible_acquirers(self, *args, currency_id=None, **kwargs):
        """ Override of payment to unlist Sips acquirers when the currency is not supported. """
        acquirers = super()._get_compatible_acquirers(*args, currency_id=currency_id, **kwargs)

        currency = self.env['res.currency'].browse(currency_id).exists()
        if currency and currency.name not in SUPPORTED_CURRENCIES:
            acquirers = acquirers.filtered(lambda a: a.provider != 'viabill')

        return acquirers

    def _sips_generate_shasign(self, data):
        """ Generate the shasign for incoming or outgoing communications.

        Note: self.ensure_one()

        :param str data: The data to use to generate the shasign
        :return: shasign
        :rtype: str
        """
        self.ensure_one()

        key = self.sips_secret
        shasign = sha256((data + key).encode('utf-8'))
        return shasign.hexdigest()

    def _get_default_payment_method_id(self):
        self.ensure_one()
        if self.provider != 'viabill':
            return super()._get_default_payment_method_id()
        return self.env.ref('payment_viabill.payment_method_viabill').id

    def _viabill_make_request(self, endpoint, data=None, method='POST'):
        """ Make a request at mollie endpoint.

        Note: self.ensure_one()

        :param str endpoint: The endpoint to be reached by the request
        :param dict data: The payload of the request
        :param str method: The HTTP method of the request
        :return The JSON-formatted content of the response
        :rtype: dict
        :raise: ValidationError if an HTTP error occurs
        """
        self.ensure_one()

        base_url = self.viabill_base_url
        _logger.info("Viabil Base Urş :\n%s", base_url)

        url = urls.url_join(base_url, endpoint)
        headers = {
            "Content-Type": "application/json",
        }
        _logger.info("1111111s")

        try:
            response = requests.request(method, url, json=data, headers=headers, timeout=60)
            _logger.info("checkout response :\n%s", pprint.pformat(response))

            response.raise_for_status()
        except requests.exceptions.RequestException:
            _logger.exception("Unable to communicate with Viabill: %s", url)
            raise ValidationError("Viabill: " + _("Could not establish the connection to the API."))
        return response.json()

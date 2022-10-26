# -*- coding: utf-8 -*-
# Part of Odoo. See LICENSE file for full copyright and licensing details.
from odoo.addons.payment.tests.common import PaymentCommon


class viabillCommon(PaymentCommon):

    @classmethod
    def setUpClass(cls, chart_template_ref=None):
        super().setUpClass(chart_template_ref=chart_template_ref)

        cls.viabill = cls._prepare_acquirer('viabill', update_values={
            'viabill_api_key': 'dummy',
        })
        cls.acquirer = cls.viabill
        cls.currency = cls.currency_euro

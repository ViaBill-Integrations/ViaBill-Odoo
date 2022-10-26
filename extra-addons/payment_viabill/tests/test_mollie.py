# -*- coding: utf-8 -*-
# Part of Odoo. See LICENSE file for full copyright and licensing details.

from odoo.tests import tagged

from .common import viabillCommon


@tagged('post_install', '-at_install')
class viabillTest(viabillCommon):

    def test_payment_request_payload_values(self):
        tx = self.create_transaction(flow='redirect')

        payload = tx._viabill_prepare_payment_request_payload()

        self.assertDictEqual(payload['amount'], {'currency': 'EUR', 'value': '1111.11'})
        self.assertEqual(payload['description'], tx.reference)

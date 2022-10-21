# -*- coding: utf-8 -*-
# Part of Odoo. See LICENSE file for full copyright and licensing details.

{
    'name': 'ViaBill Payment Acquirer',
    'version': '1.0',
    'category': 'Accounting/Payment Acquirers',
    'sequence': 356,
    'summary': 'Payment Acquirer: ViaBill Implementation',
    'description': """ViaBill Payment Acquirer""",

    'author': 'Viabill Inc.',
    'website': 'https://www.viabill.com',

    'depends': ['payment','website_sale'],
    'data': [
        'views/payment_viabill_templates.xml',
        'views/payment_views.xml',
        'data/payment_acquirer_data.xml',
        'views/product_pricetag_views.xml',
        'views/cart_pricetag_views.xml',
    ],
    'application': True,
    'uninstall_hook': 'uninstall_hook',
    'license': 'LGPL-3'
}

# Part of Odoo. See LICENSE file for full copyright and licensing details.

# List of ISO 15897 locale supported by viabill
# See full details at `locale` parameter at https://docs.viabill.com/reference/v2/payments-api/create-payment
SUPPORTED_LOCALES = [
    'en_US','es_ES', 'ca_ES','da_DK'
]

# Currency codes in ISO 4217 format supported by viabill.
# See https://docs.viabill.com/payments/multicurrency
SUPPORTED_CURRENCIES = [
     'DKK', 'EUR', 'USD'
]

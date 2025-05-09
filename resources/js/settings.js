$(() => {
    const sepayContainer = $('.sepay-connected-profile')
    const bankSubAccount = $('#payment_sepay_bank_sub_account_id')
    const unsupportedVirtualAccountBanks = ['TPBank', 'VPBank', 'VietinBank']

    const initBankSubAccount = () => {
        const bankAccountId = $('#payment_sepay_bank_account_id').val()
        const selectedBankText = $('#payment_sepay_bank_account_id option:selected').text()
        const isUnsupportedBank = unsupportedVirtualAccountBanks.some(bank => selectedBankText.split('-')[0].trim() === bank)

        console.log(isUnsupportedBank)

        if (isUnsupportedBank) {
            bankSubAccount.parent().hide()
            return
        }

        if (bankAccountId) {
            $.ajax({
                url: sepayContainer.data('get-bank-sub-accounts-url'),
                type: 'GET',
                data: {
                    bank_account_id: bankAccountId,
                },
                dataType: 'json',
                beforeSend: function () {
                    bankSubAccount.parent().hide()
                },
                success: function (response) {
                    let hasData = response.data && Object.keys(response.data).length
                    let options = '<option value="">-- Chọn tài khoản ảo --</option>'

                    if (hasData) {
                        Object.entries(response.data).forEach(([key, value]) => {
                            options += `<option value="${key}">${value}</option>`
                        })
                    }

                    bankSubAccount.html(options)

                    if (hasData) {
                        bankSubAccount.parent().show()
                        bankSubAccount.val(sepayContainer.data('bank-sub-account-id'))
                    }
                },
            })
        } else {
            bankSubAccount.html('<option value="">-- Chọn tài khoản ảo --</option>')
        }
    }

    const initPaymentCodePrefixes = () => {
        $.ajax({
            url: sepayContainer.data('get-payment-codes-url'),
            type: 'GET',
            dataType: 'json',
            success: function (response) {
                let options = ''

                if (response.data.length > 0) {
                    response.data.forEach((paymentCode) => {
                        options += `<option value="${paymentCode.prefix}">${paymentCode.prefix}</option>`
                    })
                }

                $('#payment_sepay_prefix').html(options)
                $('#payment_sepay_prefix').val(sepayContainer.data('payment-code-prefix'))
            },
        })
    }

    initBankSubAccount()
    initPaymentCodePrefixes()

    $(document).on('change', '#payment_sepay_bank_account_id', function (e) {
        initBankSubAccount()
    })
})

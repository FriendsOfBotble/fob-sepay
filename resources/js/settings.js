$(() => {
    const sepayContainer = $('.sepay-connected-profile')
    const bankSubAccount = $('#payment_sepay_bank_sub_account_id')

    const initBankSubAccount = () => {
        const bankAccountId = $('#payment_sepay_bank_account_id').val()

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
                    } else {
                        bankSubAccount.parent().hide()
                    }
                },
            })
        } else {
            bankSubAccount.html('<option value="">-- Chọn tài khoản ảo --</option>')
        }
    }

    initBankSubAccount()

    $(document).on('change', '#payment_sepay_bank_account_id', function (e) {
        initBankSubAccount()
    })
})

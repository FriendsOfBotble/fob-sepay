$(() => {
    const sepayContainer = $('.sepay-connected-profile')
    const bankSubAccount = $('#payment_sepay_bank_sub_account_id')
    const unsupportedVirtualAccountBanks = ['TPBank', 'VPBank', 'VietinBank']
    const virtualAccountRequiredBanks = (sepayContainer.data('virtual-account-required-banks') || []).map((bank) =>
        bank.toLowerCase()
    )
    const emptyOption = '<option value="">-- Chọn tài khoản ảo --</option>'

    const selectedBankName = () =>
        $('#payment_sepay_bank_account_id option:selected').text().split('-')[0].trim()

    const showSubAccountHint = (message, isError = false) => {
        let hint = bankSubAccount.parent().find('.sepay-sub-account-hint')

        if (!hint.length) {
            hint = $('<div class="sepay-sub-account-hint form-hint mt-1"></div>')
            bankSubAccount.parent().append(hint)
        }

        hint.toggleClass('text-danger', isError).text(message || '')
        hint.toggle(!!message)
    }

    const initBankSubAccount = () => {
        const bankAccountId = $('#payment_sepay_bank_account_id').val()
        const bankName = selectedBankName()
        const isUnsupportedBank = unsupportedVirtualAccountBanks.includes(bankName)
        const requiresVirtualAccount = virtualAccountRequiredBanks.includes(bankName.toLowerCase())

        showSubAccountHint('')

        if (!bankAccountId || isUnsupportedBank) {
            bankSubAccount.html(emptyOption)
            bankSubAccount.parent().hide()

            return
        }

        $.ajax({
            url: sepayContainer.data('get-bank-sub-accounts-url'),
            type: 'GET',
            data: {
                bank_account_id: bankAccountId,
            },
            dataType: 'json',
            beforeSend: () => {
                bankSubAccount.parent().hide()
            },
            success: (response) => {
                const subAccounts = (!response.error && response.data) || {}
                const hasData = Object.keys(subAccounts).length > 0
                let options = emptyOption

                Object.entries(subAccounts).forEach(([key, value]) => {
                    options += `<option value="${key}">${value}</option>`
                })

                bankSubAccount.html(options)

                if (hasData) {
                    bankSubAccount.val(sepayContainer.data('bank-sub-account-id'))
                }

                // Banks that only work through a virtual account must always show the field, otherwise
                // saving fails on a required field the admin cannot see.
                if (hasData || requiresVirtualAccount) {
                    bankSubAccount.parent().show()
                }

                if (response.error) {
                    showSubAccountHint(response.message || 'Không tải được danh sách tài khoản ảo.', true)
                } else if (!hasData && requiresVirtualAccount) {
                    showSubAccountHint(
                        `${bankName} chỉ nhận giao dịch qua tài khoản ảo (VA). Vui lòng tạo tài khoản ảo trên my.sepay.vn rồi tải lại trang này.`,
                        true
                    )
                } else if (requiresVirtualAccount) {
                    showSubAccountHint(`${bankName} bắt buộc chọn tài khoản ảo (VA).`)
                }
            },
            error: (xhr) => {
                bankSubAccount.html(emptyOption)

                if (requiresVirtualAccount) {
                    bankSubAccount.parent().show()
                    showSubAccountHint(
                        (xhr.responseJSON && xhr.responseJSON.message) || 'Không tải được danh sách tài khoản ảo.',
                        true
                    )
                }
            },
        })
    }

    const initPaymentCodePrefixes = () => {
        $.ajax({
            url: sepayContainer.data('get-payment-codes-url'),
            type: 'GET',
            dataType: 'json',
            success: (response) => {
                let options = ''

                if (Array.isArray(response.data)) {
                    response.data.forEach((paymentCode) => {
                        options += `<option value="${paymentCode.prefix}">${paymentCode.prefix}</option>`
                    })
                }

                $('#payment_sepay_prefix').html(options)
                $('#payment_sepay_prefix').val(sepayContainer.data('payment-code-prefix'))
            },
        })
    }

    if (!sepayContainer.length) {
        return
    }

    initBankSubAccount()
    initPaymentCodePrefixes()

    $(document).on('change', '#payment_sepay_bank_account_id', () => {
        initBankSubAccount()
    })
})

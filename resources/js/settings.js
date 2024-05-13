$(function () {
    $(document).on('click', '[data-bb-toggle="sepay-webhook-secret"]', (e) => {
        e.preventDefault()

        const $field = $('#sepay-webhook-secret')
        const $target = $(e.currentTarget)

        $httpClient
            .make()
            .withButtonLoading(e.currentTarget)
            .post($target.data('url'))
            .then(({ data }) => {
                $field.val(data.data.secret)
            })
    })
})

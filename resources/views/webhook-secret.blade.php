<x-core::form.text-input
    id="sepay-webhook-url"
    label="Webhook URL"
    name=""
    value="{{ route('sepay.webhook') }}"
    disabled
/>

<x-core::form.text-input
    id="sepay-webhook-secret"
    label="Mã bảo mật webhook"
    name=""
    value="{{ get_payment_setting('webhook_secret', SEPAY_PAYMENT_METHOD_NAME) ? Str::repeat('*', 32) : 'Chưa tạo mã bảo mật' }}"
    disabled
>
    <x-slot:helper-text>
        Sau khi tạo mã bảo mật, bạn không thể xem lại mã này. Vui lòng sao chép mã bảo mật này và lưu trữ ở nơi an toàn. <br/>
        Vào <a href="https://my.sepay.vn" target="_blank">SePay</a> → Xác thực thanh toán → Webhook → Thêm Webhook sau đó chọn kiểu xác thực là API key và dán mã bảo mật này vào ô API key. <br/>
        Trường hợp bạn quên hoặc chưa tạo mã, vui lòng bấm vào nút "Tạo mã bảo mật" bên dưới.
    </x-slot:helper-text>
</x-core::form.text-input>

<x-core::button
    size="md"
    data-bb-toggle="sepay-webhook-secret"
    data-url="{{ route('sepay.webhook-secret.generate') }}"
    class="mb-2"
>
    <x-core::icon name="ti ti-refresh" /> Tạo mã bảo mật
</x-core::button>

<x-core::alert type="warning">
    Lưu ý: Việc bấm vào mã này sẽ vô hiệu hóa mã bảo mật cũ, tạo mã bảo mật mới và chỉ hiển thị 1 lần duy nhất. Bạn cần cập nhật mã bảo mật mới vào SePay.
</x-core::alert>


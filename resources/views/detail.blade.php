<x-core::datagrid class="mt-3">
    <x-core::datagrid.item>
        <x-slot:title>Thông tin dữ liệu nhận từ SePay (Webhook)</x-slot:title>
        <pre><code>{{ BaseHelper::jsonEncodePrettify($payment->metadata) }}</code></pre>
    </x-core::datagrid.item>
</x-core::datagrid>

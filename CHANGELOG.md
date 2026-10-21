# Lịch sử thay đổi

Tất cả thay đổi của `fob-sepay` sẽ được mô tả trong file này

## 2.0.10 - 2026-10-21

- Bảo mật: Chuyển route kết nối/ngắt kết nối OAuth vào khu vực admin (yêu cầu quyền `payments.settings`); trước đây khách truy cập có thể ngắt kết nối SePay của cửa hàng
- Bảo mật: OAuth callback chỉ chấp nhận `state` được tạo từ lần kết nối của admin
- Bảo mật: Webhook từ chối giao dịch khi đơn hàng không phải VND và không quy đổi được sang VND (trước đây số tiền chuyển bất kỳ đều được chấp nhận)
- Sửa lỗi: Webhook gửi lại đồng thời có thể xử lý thanh toán hai lần
- Sửa lỗi: Middleware webhook trả về lỗi 500 thay vì 401 khi thiếu header `Authorization`
- Sửa lỗi: Route admin của SePay bị chặn với tài khoản admin không phải super admin
- Sửa lỗi: Mã thanh toán có thể trùng khi nhiều đơn hàng được tạo cùng lúc
- Kiểm tra trạng thái giao dịch chỉ áp dụng cho thanh toán SePay
- Webhook bỏ qua thanh toán có mã rỗng (trước đây có thể khớp với mọi giao dịch)
- Sửa lỗi: Không kích hoạt được SePay với BIDV, MSB, KienLongBank, OCB ("The payment sepay bank sub account id field is required") do ô "Tài khoản ảo" bị ẩn khi chưa có tài khoản ảo hoặc tải danh sách lỗi. Ô này luôn hiển thị kèm hướng dẫn tạo VA, và thông báo lỗi rõ ràng hơn
- Sửa lỗi: Kiểm tra tài khoản ảo đã chọn có thuộc tài khoản ngân hàng (trước đây quy tắc không có tác dụng)

## 2.0.4 - 2025-12-31

- Sửa lỗi: Áp dụng quy tắc xác thực chỉ cho phương thức thanh toán SePay (Fixes #4)

## 2.0.3 - 2025-11-05

- Sửa lỗi: Trường prefix mã thanh toán không thể chọn được

## 2.0.0 - 2025-05-16

- Tích hợp OAuth2 SePay để tối ưu hóa quá trình tích hợp SePay vào Botble

## 1.0.2 - 2024-05-19

- Tối ưu webhook, thêm xác thực đơn hàng sau xác thực thanh toán

## 1.0.0 - 2024-05-14

- Bản phát hành đầu tiên

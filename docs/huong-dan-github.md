# Hướng dẫn đưa dự án lên GitHub và dùng Issues

Repo trong file zip đã có sẵn lịch sử commit theo Conventional Commits. Các bước dưới đây cần tài khoản GitHub của bạn.

## 1. Tạo repo và đẩy code

```bash
# Tạo repo trống trên github.com (không tick README), ví dụ ngoi-photobooth
cd ngoi-photobooth
git remote add origin https://github.com/<tài-khoản>/ngoi-photobooth.git
git push -u origin main
git checkout -b develop && git push -u origin develop
```

Hoặc với GitHub CLI: `gh repo create ngoi-photobooth --private --source . --push`.

## 2. Bật bảo vệ nhánh
Settings → Branches → Add rule cho `main` và `develop`: *Require a pull request*, *Require status checks* (chọn các job của **CI**), *Require 1 approval*.

## 3. Tạo nhãn và Issues
```bash
gh auth login
scripts/create-github-issues.sh <tài-khoản>/ngoi-photobooth
```
Script tạo 7 nhãn và 19 Issue theo backlog; Issue đã làm xong được đóng kèm ghi chú, 3 Issue còn lại (triển khai, UAT, gửi link ảnh) để mở.
Sau đó tạo **Projects** (tab Projects → New project → Board) và thêm các Issue vào.

## 4. Làm việc hằng ngày
```bash
git checkout develop && git pull
git checkout -b feature/18-trien-khai-may-chu
# … sửa code, chạy scripts/test.sh …
git commit -m "chore(deploy): cấu hình domain và HTTPS

Closes #18"
git push -u origin feature/18-trien-khai-may-chu
gh pr create --base develop --fill
```
CI chạy tự động trên PR. Merge xong Issue #18 tự đóng.

## 5. Phát hành
```bash
git checkout main && git merge --no-ff develop && git tag v1.0.0 && git push --follow-tags
```
Workflow **Release** build image `ghcr.io/<tài-khoản>/ngoi-photobooth:1.0.0` và tạo GitHub Release.

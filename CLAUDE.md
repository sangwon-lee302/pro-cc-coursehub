# CourseHub

オンライン学習プラットフォーム。

## 技術スタック

- PHP 8.2
- Laravel 10
- Laravel Sail（Docker 開発環境）
- MySQL 8.0
- Blade + Tailwind CSS

## コース構造

Course > Chapter > Lesson > Quiz の階層構造。

- Course: コーチが作成する講座
- Chapter: Course 内の章（並び順を持つ）
- Lesson: Chapter 内のレッスン（本文コンテンツ）
- Quiz: Lesson に紐づく小テスト（Question / Option で構成）

## 開発環境

Docker Compose（Sail）で起動:

```bash
./vendor/bin/sail up -d
```

マイグレーション:

```bash
./vendor/bin/sail artisan migrate
```

シーディング:

```bash
./vendor/bin/sail artisan db:seed
```

## ユーザーロール

- admin: 管理者
- coach: コーチ（コース作成）
- student: 受講生

## テスト

```bash
./vendor/bin/sail artisan test
```

## コーディング規約・設計方針

README.md のコーディング規約・設計方針セクションを参照。

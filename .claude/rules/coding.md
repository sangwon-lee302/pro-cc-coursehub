---
description: コーディング規約
globs: "app/**/*.php"
---

- Controller ではバリデーションに Form Request を使用する（既存コードには直書きの `validate()` が残るが、新規コードでは規約に従う）
- Controller は薄く保つ。複雑なビジネスロジックや複数モデルにまたがる処理は `app/Services/` の Service クラスに切り出す（既存に Fat Controller が残るが、新規コードでは規約に従う）
- リソースのアクセス制御は Policy で実装する。Form Request を使わないアクションは Controller で `$this->authorize()` を使用し、Form Request を使うアクションはその Form Request の `authorize()` 内で認可する
- 重要なドメインイベントは Event/Listener パターンで実装する
- モデルのリレーションは明示的に定義する
- 変数名・メソッド名は camelCase、テーブル名・カラム名は snake_case
- Blade 画面はリダイレクト + フラッシュメッセージで結果・エラーを返す
- 新機能には Feature テストを書く（テストメソッド名は `test_` プレフィックス）
- 詳細は README.md のコーディング規約・設計方針セクションを参照

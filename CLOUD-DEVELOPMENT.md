# iPhoneから開発を続ける

1. Codexでクラウド環境を選び、このリポジトリの修正を依頼します。
2. 修正内容をブランチに保存して、構文チェックと必要なテストを実行します。
3. `python3 scripts/package-plugin.py . --output dist` でインストール用ZIPを作れます。
4. GitHubのActions → Manual test ZIP → Run workflowから対象ブランチを選ぶと、ZIPとSHA-256をArtifactsからダウンロードできます。Artifactの外側のZIPを展開し、中の製品ZIPをWordPressにアップロードしてください。
5. 本番ではなくテスト用WordPressで、有効化・編集・保存・復元を確認します。
6. 確認できた変更だけを取り込みます。公開版の配布は既存のReleaseワークフローを使用します。

Macの起動は不要ですが、Codex/GitHubへのログインと利用枠、テストサイトへのアクセスは必要です。
GitHub上のこのファイルとAGENTS.mdは、クラウドの新しい会話でも参照できます。
アルファ版・ベータ版を公開リポジトリへコミットしないでください。

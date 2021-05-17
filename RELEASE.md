# RELEASE

バージョニングはセマンティックバージョニングでは**ありません**。

| バージョン   | 説明
|:--           |:--
| メジャー     | 大規模な仕様変更の際にアップします（クラス構造・メソッド体系などの根本的な変更）。<br>メジャーバージョンアップ対応は多大なコストを伴います。
| マイナー     | 小規模な仕様変更の際にアップします（中機能追加・メソッドの追加など）。<br>マイナーバージョンアップ対応は1日程度の修正で終わるようにします。
| パッチ       | バグフィックス・小機能追加の際にアップします（基本的には互換性を維持するバグフィックス）。<br>パッチバージョンアップは特殊なことをしてない限り何も行う必要はありません。

なお、下記の一覧のプレフィックスは下記のような意味合いです。

- change: 仕様変更
- feature: 新機能
- fixbug: バグ修正
- refactor: 内部動作の変更
- `*` 付きは互換性破壊

## x.y.z

- 選択ソートを使えばソート中の出力が行えるかも？

## 1.0.6

- [change] command information
- [feature] groupBy を実装
- [refactor] evaluate のシンプル化
- [feature] gz/bz2 に対応
- [feature] jsonl を追加
- [fixbug] json+compact+comment で無駄な空白があったので削除
- [change] yaml がパースできない環境があるのでドキュメント形式はやめてリスト形式に変更
- [change] エラーは stderr とし、php のログには出さない

## 1.0.5

- [fixbug] select した列でしか order-by できない不具合を修正
- [feature] 先頭1行の先読みに対応
  - md や tsv などで "*" や "~col" 指定でカラムが表示されるようになった
- [feature] distinct を実装
- [feature] Stream Wrapper の glob に対応

## 1.0.4

- [feature] 出力ハイライトに対応
- [feature] ssh config の読み込みに対応
- [fixbug] 同じ接続箇所で複数接続してしまう不具合を修正

## 1.0.3

- [feature] regex オプションを実装
- [feature] sftp に対応
- [fixbug] 行番号が+1で表示される不具合を修正

## 1.0.2

- [feature] below-where を実装

## 1.0.1

- [feature] compact モードを追加
- [feature] order-by を実装

## 1.0.0

- 公開

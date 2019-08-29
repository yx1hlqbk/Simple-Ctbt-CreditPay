# Simple-Ctbt-CreditPay
中國信託信用卡付款

# 環境
- php 5

# 備註
- 關於壓碼字串，這部分你得上中國信託金流後台去作申請
- 關於auth_mpi_mac.php這隻檔案，由於擔心會衍生出其他問題，所以就不提供

# 操作

<h3>設定記本參數</h3>

```php
$config = [
    'MerchantID' => '', //特定代號
    'TerminalID' => '', //終端機代號
    'merId' => '', //專用代號
    'Key' => '', //壓碼字串
    'MerchantName' => '測試店家', //店家名稱
    'AuthResURL' => '', //回傳位置
    'txType' => '0', //交易方式
    'AutoCap' => '1', //請款方式
    'debug' => '0', //除錯
    'Customize' => '1' //語系
];
$Ctbt = new Ctbt($config);
```

<h3>信用卡付款</h3>

```php
$orderInfo = [
    'lidm' => '', //訂單編號
    'purchAmt' => '', //金額
    'OrderDetail' => '', //訂單敘述
    'Option' => '', //交易敘述
];
$Ctbt->pay($orderInfo);
```

<h3>伺服端回傳驗證</h3>

```php
$result = $Ctbt->returnVerification();
```

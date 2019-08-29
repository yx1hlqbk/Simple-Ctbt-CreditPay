<?php
require_once 'auth_mpi_mac.php';

/**
 *
 */
class Ctbc
{
    /**
     * 特定代號
     */
	private $MerchantID;

    /**
     * 終端機代號
     */
	private $TerminalID;

    /**
     * 專用代號
     */
	private $merId;

    /**
     * 貴特店在URL帳務管理後台登錄的壓碼字串
     */
	private $Key;

    /**
     * 商店名稱
     */
	private $MerchantName;

    /**
     * Server端回傳驗證位置
     */
    private $AuthResURL;

    /**
     * 交易方式
     *
     * 一般交易 : 0
     * 分期交易 : 1
     * 紅利折抵一般交易 : 2
     * 紅利折抵分期交易 : 4
     */
	private $txType;

    /**
     * 交易方式敘述
     *
     * 一般交易 : 填寫 1
     * 分期 : 填寫分期期數
     * 紅利交易 : 填寫固定兩碼的商品代碼
     * 紅利分期 : 填寫四碼，1-2碼為產品代碼，3-4碼為分期期數
     */
	private $Option;

    /**
     * 請款方式
     *
     * 不自動請款 : 0
     * 自動請款 : 1
     */
    private $AutoCap;

    /**
     * 除錯
     *
     * 不開 : 0
     * 開 : 1
     */
	private $debug;

    /**
     * 訂單編號
     */
	private $lidm;

    /**
     * 消費金額
     */
	private $purchAmt;

    /**
     * 訂單描述
     */
	private $OrderDetail = '';

    /**
     * 語系
     *
     * 繁中 : 1
     * 簡中 : 2
     * 英文 : 3
     * 客製化頁面 : 5
     */
	private $Customize;


    /**
     * 傳送位置
     */
	// private $test_url = "https://testepos.ctbcbank.com/auth/SSLAuthUI.jsp";
	private $online_url = "https://epos.chinatrust.com.tw/auth/SSLAuthUI.jsp";

    /**
     * Ctbt Construct
     *
     * @param Array $config
     */
    function __construct($config = [])
    {
        if (empty($config)) {
            die('請先填入變數。');
        }

        foreach ($config as $key => $value) {
            $this->$key = $value;
        }
    }

    /**
     * Ctbt init
     *
     * @param Array $config
     */
	public function init($config)
	{
        if (empty($config)) {
            die('請先填入變數。');
        }

        foreach ($config as $key => $value) {
            $this->$key = $value;
        }
	}

    /**
     * Ctbt 付款
     *
     * @param Array $config
     */
	public function pay($config = [])
	{
        $this->init($config);

		$URLEnc = $this->encryptCode();

		$html = '
            <p>頁面轉向中，請稍後...</p>
            <form name="autoForm" method="post" action="'.$this->online_url.'">
                <input type="hidden" value="'.$this->merId.'" name="merID" />
                <input type="hidden" value="'.$URLEnc.'" name="URLEnc" length="100" />
            </form>
            <script>autoForm.submit();</script>';

		echo $html;
	}

    /**
     * 壓密資料
     */
	private function encryptCode()
	{
		$MACString = auth_in_mac($this->MerchantID,$this->TerminalID,$this->lidm,$this->purchAmt,
		$this->txType,$this->Option,$this->Key,$this->MerchantName,$this->AuthResURL,
		$this->OrderDetail,$this->AutoCap,$this->Customize,$this->debug);

		$URLEnc = get_auth_urlenc($this->MerchantID,$this->TerminalID,$this->lidm,$this->purchAmt,
		$this->txType,$this->Option,$this->Key,$this->MerchantName,$this->AuthResURL,
		$this->OrderDetail,$this->AutoCap,$this->Customize,$MACString,$this->debug);

		return $URLEnc;
	}

    /**
     * 回傳資料驗證
     */
	public function returnVerification()
	{
		$EncArray = gendecrypt($_POST['URLResEnc'],$this->Key, $this->debug);

		$status = isset($EncArray['status']) ? $EncArray['status'] : "";
		$errCode = isset($EncArray['errcode']) ? $EncArray['errcode'] : "";
		$authCode = isset($EncArray['authcode']) ? $EncArray['authcode'] : "";
		$authAmt = isset($EncArray['authamt']) ? $EncArray['authamt'] : "";
		$lidm = isset($EncArray['lidm']) ? $EncArray['lidm'] : "";
		$OffsetAmt = isset($EncArray['offsetamt']) ? $EncArray['offsetamt'] : "";
		$OriginalAmt = isset($EncArray['originalamt']) ? $EncArray['originalamt'] : "";
		$UtilizedPoint = isset($EncArray['utilizedpoint']) ? $EncArray['utilizedpoint'] : "";
		$Option = isset($EncArray['numberofpay']) ? $EncArray['numberofpay'] : "";
		$Last4digitPAN = isset($EncArray['last4digitpan']) ? $EncArray['last4digitpan'] : "";
		$pidResult= isset($EncArray['pidResult']) ? $EncArray['pidResult'] : "";
		$CardNumber = isset($EncArray['CardNumber']) ? $EncArray['CardNumber'] : "";

		$MACString = auth_out_mac($status,$errCode,$authCode,$authAmt,
		$lidm,$OffsetAmt,$OriginalAmt,$UtilizedPoint,$Option,$Last4digitPAN,
		$this->Key,$this->debug);

		if ($MACString==$EncArray['outmac']) {
            if ($EncArray['status'] == '0') {
                return [
                    'status'=> 'Y',
                    'message' => '刷卡成功。',
                    'data' => [
                        'lidm' => $lidm,
                        'authcode' => $authCode,
                    ]
                ];
            } else {
                header("Content-Type:text/html;charset=big5");
                return [
                    'status'=> 'N',
                    'message' => $EncArray['errdesc']
                ];
            }
		}
		else {
			return [
                'status'=> 'N',
				'message' => '刷卡失敗。'
            ];
		}
	}

	// status=>  狀態 0=成功
	// errcode=> 錯誤編號 00=成功
	// errdesc=> 錯誤描述
	// outmac=>  回傳編碼，可再做一次加密確認
	// merid=>  商店代碼
	// authamt=>  金額
	// lidm=>  訂單編號
	// xid=>E26C125C00010759068_811200076  此次授權之交易序號，最長為 此次授權之交易序號，最長為 此次授權之交易序號，最長為 40 個位元文字串 (此值為系統 的 內定Unquie 值)。
	// authcode=> 交易授權碼，最大長度為 6 的
	// termseq=> 取得 調閱序號 。
	// last4digitpan=> 卡號後四碼
	// cardnumber=>
	// authresurl=>  回傳位置
	// numberofpay=>  分期的付款數 ，純數字長度為 0 ~ 2 。(分期特店與紅 分期特店與紅 利折抵分期特店必填 利折抵分期特店必填 ，其餘特店免填 ，其餘特店免填 )
}

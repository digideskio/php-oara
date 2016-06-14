<?php
/**
 The goal of the Open Affiliate Report Aggregator (OARA) is to develop a set
 of PHP classes that can download affiliate reports from a number of affiliate networks, and store the data in a common format.

 Copyright (C) 2014  Fubra Limited
 This program is free software: you can redistribute it and/or modify
 it under the terms of the GNU Affero General Public License as published by
 the Free Software Foundation, either version 3 of the License, or any later version.
 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU Affero General Public License for more details.
 You should have received a copy of the GNU Affero General Public License
 along with this program.  If not, see <http://www.gnu.org/licenses/>.

 Contact
 ------------
 Fubra Limited <support@fubra.com> , +44 (0)1252 367 200
 **/
/**
 * Export Class
 *
 * @author     Carlos Morillo Merino
 * @category   Oara_Network_Publisher_Amazon
 * @copyright  Fubra Limited
 * @version    Release: 01.00
 *
 */
class Oara_Network_Publisher_Amazon extends Oara_Network {
	public function __construct($credentials) {
		$this->_credentials = $credentials;

	}

	/**
	 * Check the connection
	 */
	public function checkConnection() {
		//If not login properly the construct launch an exception
		$connection = true;

		$url = "https://assoc-datafeeds-eu.amazon.com/datafeed/listReports";
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_USERPWD, $this->_credentials["user"].':'.$this->_credentials["password"]);
		curl_setopt($curl, CURLOPT_HEADER, false);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
		curl_setopt($curl, CURLOPT_BINARYTRANSFER, true);
		$output = curl_exec($curl);
		if (preg_match("/Error/", $output)){
			$connection = false;
		}
		return $connection;
	}
	/**
	 * (non-PHPdoc)
	 * @see library/Oara/Network/Oara_Network_Publisher_Interface#getMerchantList()
	 */
	public function getMerchantList() {
		$merchants = array();

		$obj = array();
		$obj['cid'] = "1";
		$obj['name'] = "Amazon";
		$obj['url'] = "www.amazon.com";
		$merchants[] = $obj;

		return $merchants;
	}

	/**
	 * (non-PHPdoc)
	 * @see library/Oara/Network/Oara_Network_Publisher_Interface#getTransactionList($aMerchantIds, $dStartDate, $dEndDate, $sTransactionStatus)
	 */
	public function getTransactionList($merchantList = null, Zend_Date $dStartDate = null, Zend_Date $dEndDate = null, $merchantMap = null) {

		$totalTransactions = array();
		$dateArray = Oara_Utilities::daysOfDifference ( $dStartDate, $dEndDate );
		$dateArraySize = sizeof ( $dateArray );
		for($j = 0; $j < $dateArraySize; $j++) {
			$date = $dateArray[$j]->toString("yyyyMMdd");

			$url = "https://assoc-datafeeds-eu.amazon.com/datafeed/getReport?filename={$this->_credentials["user"]}-earnings-report-$date.tsv.gz";
			$curl = curl_init();
			curl_setopt($curl, CURLOPT_URL, $url);
			curl_setopt($curl, CURLOPT_USERPWD, $this->_credentials["user"].':'.$this->_credentials["password"]);
			curl_setopt($curl, CURLOPT_HEADER, false);
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
			curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
			curl_setopt($curl, CURLOPT_BINARYTRANSFER, true);
			$output = curl_exec($curl);
			if ($output){
				$filename = realpath ( dirname ( COOKIES_BASE_DIR ) ) . "/pdf/{$this->_credentials["user"]}-earnings-report-$date.tsv.gz";
				file_put_contents($filename, $output);
				$zd = gzopen($filename, "r");
				$contents = gzread($zd, 10000);
				gzclose($zd);

				$exportData = explode("\n", $contents);

				$num = count($exportData);
				for ($i = 2; $i < $num; $i++) {
					$transactionExportArray = explode("\t",$exportData[$i]);

					$transaction = Array();
					$transaction['merchantId'] = 1;
					$transaction['date'] = $dateArray[$j]->toString("yyyy-MM-dd HH:mm:ss");
					if ($transactionExportArray[2] != null) {
						$transaction['custom_id'] = $transactionExportArray[2];
					}

					$transaction['status'] = Oara_Utilities::STATUS_CONFIRMED;
					$transaction['amount'] = Oara_Utilities::parseDouble($transactionExportArray[7]);
					$transaction['commission'] = Oara_Utilities::parseDouble($transactionExportArray[8]);
					$totalTransactions[] = $transaction;

				}
				unlink($filename);
			}
		}


		return $totalTransactions;
	}



}

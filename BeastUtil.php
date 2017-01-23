<?php
	/**
	 * 召喚獣ユーティリティ222
	 */
	/**
	 * 召喚獣ユーティリティ
	 */
	class BeastUtil {
		
		/**
		 * 召喚獣のデッキNoを取得する。
		 * @param $bf2UserActualInfo
		 */
		public static function getBeastDeckNo($bf2UserActualInfo) {
			// デッキNo
			$deck_no = $bf2UserActualInfo->getDeck_no();
			if ($bf2UserActualInfo->getBeast_deck_no() > UNIT_DECK_NONE) {
				// デッキNOが有効値
				$deck_no = $bf2UserActualInfo->getBeast_deck_no();
			}
			return $deck_no;
		}
		
		/**
		 * 召喚獣情報を取得する。
		 * @param $bf2UserBeastDeckInfo
		 * @param $bf2UserBeastInfoList
		 */
		public static function getLpsUserBeastInfoByBeastDeck($bf2UserBeastDeckInfo, $bf2UserBeastInfoList) {
			
			if ($bf2UserBeastDeckInfo != null) {
				
				// 召喚獣IDリスト
				$beastIdList = array();
				
				// ユーザーID
				$user_id = $bf2UserBeastDeckInfo->getUser_id();
				// 召喚獣情報
				$beastInfo = $bf2UserBeastDeckInfo->getBeast_info();
				// 分解
				$beastInfoList = ArrayUtil::explode(CONNMA, $beastInfo);
				foreach ($beastInfoList as $beastInfoVal) {
					list($index, $beastId) = ArrayUtil::explode(COLON, $beastInfoVal);
					// 追加
					$beastIdList[$beastId] = $beastId;
				}
				
				foreach ($beastIdList as $beastId) {
					if (isset($bf2UserBeastInfoList[$beastId])) {
						// 持っている
						unset($beastIdList[$beastId]);
					}
				}
				
				if (count($beastIdList) != 0) {
					$bf2UserBeastInfoTargetList = Bf2_user_beast_info_DAO::getListIn($user_id, $beastIdList, DB_TYPE_MST);
					
					// ベースリストに追加
					foreach ($bf2UserBeastInfoTargetList as $bf2UserBeastInfoTarget) {
						$bf2UserBeastInfoList[$bf2UserBeastInfoTarget->getBeast_id()] = $bf2UserBeastInfoTarget;
					}
				}
			}
			
			return $bf2UserBeastInfoList;
		}
		
		/**
		 * 召喚獣のピース情報を取得する。
		 * @param $bf2UserBeastInfoList
		 * @param $bf2UserBeastPieceInfoList
		 */
		public static function getLpsUserBeastPieceInfoList($bf2UserBeastInfoList, $bf2UserBeastPieceInfoList) {
			
			if (count($bf2UserBeastInfoList) != 0) {
				
				// 召喚獣IDリスト
				$beastIdList = array();
				
				// ユーザーID
				$user_id = STATE_NO;
				
				foreach ($bf2UserBeastInfoList as $beastId => $bf2UserBeastInfo) {
					$beastIdList[$beastId] = $beastId;
					// 設定
					$user_id = $bf2UserBeastInfo->getUser_id();
				}
				
				foreach ($beastIdList as $beastId) {
					if (isset($bf2UserBeastPieceInfoList[$beastId])) {
						// 持っている
						unset($beastIdList[$beastId]);
					}
				}
				
				if (count($beastIdList != 0)) {
					$bf2UserBeastPieceInfoTargetList = Bf2_user_beast_piece_info_DAO::getListIn($user_id, $beastIdList, DB_TYPE_MST);
					
					// ベースに追加
					foreach ($bf2UserBeastPieceInfoTargetList as $bf2UserBeastPieceInfoTarget) {
						$bf2UserBeastPieceInfoList[$bf2UserBeastPieceInfoTarget->getBeast_id()] = $bf2UserBeastPieceInfoTarget;
					}
				}
			}
			
			return $bf2UserBeastPieceInfoList;
		}

		/**
		 * 召喚獣クラスアップにおける、必要素材がそろっているかどうかチェック
		 * チェックと同時に間引きも行う
		 * @param unknown $bf2UserTeamInfo
		 * @param unknown $userItemElemList
		 * @param unknown $bf2UserItemInfoElemList
		 */
		public static function checkBeastClassUpNeedElem(&$bf2UserTeamInfo, &$userItemElemList, &$bf2UserItemInfoElemList) {

			foreach ($userItemElemList as $item_id => $item_prossession) {

				foreach ($bf2UserItemInfoElemList as $bf2UserItemInfoElem) {

					if ($item_id == $bf2UserItemInfoElem->getItem_id()) {

						$prossession = $bf2UserItemInfoElem->getPossession();

						if ($prossession >= $item_prossession) {
							// 所持数の方が多い
							// 初期数を保持
							$bf2UserItemInfoElem->setDefaultPossession($prossession);
							// 減算
							$bf2UserItemInfoElem->setPossession($prossession - $item_prossession);

						} else {
							// 所持数が足りない
							Validator::throwError(MSG_NO_CLASS_UP_ELEM_ITEM, MESSAGE_TYPE_ASSUME_ERR, MESSAGE_KBN_RETURN);
						}
					}
				}
			}
		}

		/**
		 * 召喚獣の合成金額を算出する
		 * @param $bf2UserBeastInfoBase
		 * @param $userItemElemList
		 */
		public static function calcBeastMixAmount($bf2UserBeastInfoBase, &$userItemElemList) {

			// データキャッシュユーティリティ
			global $g_dataCacheUtil;

			// 素材合計金額
			$itemAmountRst = 0;

			// 素材金額合計
			foreach ($userItemElemList as $itemId => $userItemElem) {

				$bf2ItemMst = $g_dataCacheUtil->getLpsItemMst($itemId);

				$itemAmountRst += $bf2ItemMst->getBuy_gil();
			}

			// 金額
			$amountRst = $bf2UserBeastInfoBase->getLv() * ($itemAmountRst + 100);

			// 少数第1位で四捨五入
			$amountRst = round($amountRst, 1);

			return $amountRst;
		}

		/**
		 * 該当レベルのステータス設定
		 * @param $bf2UserBeastInfo
		 */
		public static function setBeastLvStatus(&$bf2UserBeastInfo) {

			// データキャッシュユーティリティ
			global $g_dataCacheUtil;

			// 召喚獣マスタ
			$bf2BeastStatusMst = $g_dataCacheUtil->getLpsBeastStatusMstSingle($bf2UserBeastInfo->getBeast_id(), $bf2UserBeastInfo->getRare());

			// 成長マスタ
			$bf2BeastGrowMst = $g_dataCacheUtil->getLpsBeastGrowMstSingle($bf2BeastStatusMst->getMax_lv(), $bf2BeastStatusMst->getGrowth_type(), $bf2UserBeastInfo->getLv());

			// 成長タイプ
			$growthRate = $bf2BeastGrowMst->getGrowth_rate();

			// HP
			$plusHp = self::calcBeastParam($bf2BeastStatusMst->getHpMin(), $bf2BeastStatusMst->getHpMax(), $growthRate);
			$bf2UserBeastInfo->setHp($bf2BeastStatusMst->getHpMin() + $plusHp);
			// MP
			$plusMp = self::calcBeastParam($bf2BeastStatusMst->getMpMin(), $bf2BeastStatusMst->getMpMax(), $growthRate);
			$bf2UserBeastInfo->setMp($bf2BeastStatusMst->getMpMin() + $plusMp);
			// ATK
			$plusAtk = self::calcBeastParam($bf2BeastStatusMst->getAtkMin(), $bf2BeastStatusMst->getAtkMax(), $growthRate);
			$bf2UserBeastInfo->setAtk($bf2BeastStatusMst->getAtkMin() + $plusAtk);
			// DEF
			$plusDef = self::calcBeastParam($bf2BeastStatusMst->getDefMin(), $bf2BeastStatusMst->getDefMax(), $growthRate);
			$bf2UserBeastInfo->setDef($bf2BeastStatusMst->getDefMin() + $plusDef);
			// INT
			$plusInt = self::calcBeastParam($bf2BeastStatusMst->getIntelligenceMin(), $bf2BeastStatusMst->getIntelligenceMax(), $growthRate);
			$bf2UserBeastInfo->setIntelligence($bf2BeastStatusMst->getIntelligenceMin() + $plusInt);
			// MND
			$plusMnd = self::calcBeastParam($bf2BeastStatusMst->getMindMin(), $bf2BeastStatusMst->getMindMax(), $growthRate);
			$bf2UserBeastInfo->setMind($bf2BeastStatusMst->getMindMin() + $plusMnd);

		}

		/**
		 * LVステータス情報追加
		 * @param $lvUpStatusList
		 * @param $bf2UserBeastInfoBase
		 */
		private static function addLvStatus(&$lvUpStatusList, $bf2UserBeastInfoBase) {

			// 結果情報
			$lvStatus = "";

			// LV
			$lvStatus .= $bf2UserBeastInfoBase->getLv();
			// HP
			$lvStatus .= COLON . $bf2UserBeastInfoBase->getHp();
			// MP
			$lvStatus .= COLON . $bf2UserBeastInfoBase->getMp();
			// ATK
			$lvStatus .= COLON . $bf2UserBeastInfoBase->getAtk();
			// DEF
			$lvStatus .= COLON . $bf2UserBeastInfoBase->getDef();
			// INT
			$lvStatus .= COLON . $bf2UserBeastInfoBase->getIntelligence();
			// MND
			$lvStatus .= COLON . $bf2UserBeastInfoBase->getMind();
			// CP
			$lvStatus .= COLON . $bf2UserBeastInfoBase->getCp();

			// 追加
			$lvUpStatusList[] = $lvStatus;
		}

		/**
		 * 最小値・最大値から現在のパラメータ値を算出する。
		 * @param $minVal
		 * @param $maxVal
		 * @param $grow_rate
		 */
		public static function calcBeastParam($minVal, $maxVal, $grow_rate) {

			$result = 0;

			// 上昇値を取得
			$diffVal = $maxVal - $minVal;

			// 成長割合を算出
			$growVal = NumericUtil::normalization($diffVal * $grow_rate / 100);

			// 小数点切り捨て
			$result = NumericUtil::castIntFloor($growVal);

			return $result;
		}

		/**
		 * 召喚獣ピースを開放するにを更新する。(CPも減算)
		 * @param $userBeastPieceInfo
		 * @param $openPieceIdList
		 */
		public static function openBeastPiece(&$userBeastPieceInfo, &$bf2UserBeastInfo, $openPieceIdList) {
			
			// データキャッシュユーティリティ
			global $g_dataCacheUtil;

			// ユーザーの召喚獣ID
			$beast_id = $userBeastPieceInfo->getBeast_id();

			// 開放ピース設定
			foreach ($openPieceIdList as $openPieceId) {

				// ピースマスタ
				$bf2BeastBoardPieceMst = $g_dataCacheUtil->getLpsBeastBoardPieceMstSingle($openPieceId);

				// 該当ビット位置
				$index = $bf2BeastBoardPieceMst->getPiece_index();

				// スイッチ確認 && 召喚獣IDの整合確認
				if ($beast_id == $bf2BeastBoardPieceMst->getBeast_id() && !SwitchUtil::isSwitchTrue($userBeastPieceInfo->getPiece_info(), $index)) {
					// index を取得してその位置のビットを変更
					$userBeastPieceInfo->updatePieceInfo(STATE_YES, $index);

					// スイッチONでCPを減算
					$bf2UserBeastInfo->setCp($bf2UserBeastInfo->getCp() - $bf2BeastBoardPieceMst->getCp());
				}
			}
		}

		/**
		 * ピース情報(CSV形式文字列)からピースIDリストを作成する
		 * @param $pieceInfo
		 */
		public static function createPieceIdListFromPieceInfo($pieceInfo) {

			$resultPieceIdList = array();

			// ピース情報を分解
			if (!StringUtil::isNullOrBlank($pieceInfo)) {
				$resultPieceIdList = ArrayUtil::explode(CONNMA, $pieceInfo);
			}

			return $resultPieceIdList;
		}

		/**
		 * ピースIDリストから合計CPを算出する
		 * @param $pieceIdList
		 */
		public static function calcTotalCpByPieceIdList($pieceIdList) {
			// データキャッシュユーティリティ
			global $g_dataCacheUtil;

			$resultCp = 0;
			foreach ($pieceIdList as $pieceId) {
				$resultCp += $g_dataCacheUtil->getLpsBeastBoardPieceMstSingle($pieceId)->getCp();
			}

			return $resultCp;
		}

		/**
		 * ユーザー別ユニット情報のBefore/Afterの文字列を生成する。
		 * @param $bf2UserBeastInfoBefore
		 * @param $bf2UserBeastInfoAfter
		 */
		public static function getBeastLvInfo($bf2UserBeastInfoBefore, $bf2UserBeastInfoAfter) {

			// 同一のユーザー別ユニットID
			$beastLvInfo = $bf2UserBeastInfoBefore->getBeast_id();

			// 繋ぎ
			$beastLvInfo .= ATMARK;

			// BEFORE
			$beastLvInfo .= self::createCSV($bf2UserBeastInfoBefore);

			// 繋ぎ
			$beastLvInfo .= UNDER_BAR;

			// AFTER
			$beastLvInfo .= self::createCSV($bf2UserBeastInfoAfter);

			return $beastLvInfo;
		}

		/**
		 * CSVデータを作成
		 */
		public static function createCSV($bf2UserBeastInfo) {
			$beastLvInfo = "";
			//$beastLvInfo .= $bf2UserBeastInfo->getBeast_id()      . COLON;
			$beastLvInfo .= $bf2UserBeastInfo->getRare()          . COLON;
			$beastLvInfo .= $bf2UserBeastInfo->getLv()            . COLON;
			$beastLvInfo .= $bf2UserBeastInfo->getExp()           . COLON;
			$beastLvInfo .= $bf2UserBeastInfo->getTotal_exp()     . COLON;
			$beastLvInfo .= $bf2UserBeastInfo->getHp()            . COLON;
			$beastLvInfo .= $bf2UserBeastInfo->getMp()            . COLON;
			$beastLvInfo .= $bf2UserBeastInfo->getAtk()           . COLON;
			$beastLvInfo .= $bf2UserBeastInfo->getDef()           . COLON;
			$beastLvInfo .= $bf2UserBeastInfo->getIntelligence()  . COLON;
			$beastLvInfo .= $bf2UserBeastInfo->getMind()          . COLON;
			$beastLvInfo .= $bf2UserBeastInfo->getCp()            . COLON;

			return $beastLvInfo;
		}
        
        /**
         * ユーザーの幻獣関連情報のポイントリセット ※参照渡しの方がわかりやすいかも
         * @param string $user_id
         * @param \int $beastidList
         * @return [\Bf2_user_beast_info, \Bf2_user_beast_piece_info]
         */
        public static function resetCpLpsUserBeastInfo($user_id, $beastidList){
            
            // ユーザー別幻獣各種情報
            $bf2UserBeastInfoList = Bf2_user_beast_info_DAO::getListIn($user_id, $beastidList, DB_TYPE_MST);
            $bf2UserBeastPieceInfoList = Bf2_user_beast_piece_info_DAO::getListIn($user_id, $beastidList, DB_TYPE_MST);
            
            // 一応、複数対応も入れておく
            foreach ($beastidList as $beastId) {
                    
                $bf2UserBeastInfo = &$bf2UserBeastInfoList[$beastId];
                $bf2UserBeastPieceInfo = &$bf2UserBeastPieceInfoList[$beastId];
                
                // 返還CP値計算
                $calcReturnCp = self::calcReturnCp($beastId, $bf2UserBeastPieceInfo);
                
                // 返還値が0の場合、エラー
                if($calcReturnCp <= 0){
                    Validator::throwError(MSG_NOT_BEAST_POINT_RESET, MESSAGE_TYPE_ASSUME_ERR, MESSAGE_KBN_RETURN);
                }
            
                // 返還値を加える
                $bf2UserBeastInfo->setCp($bf2UserBeastInfo->getCp() + $calcReturnCp);
                // リセット回数足しこみ
                $bf2UserBeastInfo->setReset_cnt($bf2UserBeastInfo->getReset_cnt() + 1);
            }
            
            return [$bf2UserBeastInfoList, $bf2UserBeastPieceInfoList];
        }
        
        /**
         * ユーザーの幻獣リセットが初回かどうか
         * @param string $user_id
         * @return boolean
         */
        public static function isFirstUserBeastReset($user_id){
            
            // ユーザー別幻獣情報を精査
            foreach (Bf2_user_beast_info_DAO::getList($user_id, DB_TYPE_MST) as $bf2UserBeastInfo) {
                
                // リセット回数を検定する
                if($bf2UserBeastInfo->getReset_cnt() > 0){
                    return false;
                }
            }
            
            return true;
        }

        /**
         * 返還するCP値の計算
         * @global DataCacheUtil $g_dataCacheUtil
         * @param \Bf2_user_beast_piece_info $bf2UserBeastPieceInfo
         * @param int $beastId
         * @return int 返却値
         */
        private static function calcReturnCp($beastId, &$bf2UserBeastPieceInfo){
            
            global $g_dataCacheUtil;
            
            $ret = 0;
            
            // 該当フラグが上がっているCP値を戻すため、計算
            foreach ($g_dataCacheUtil->getLpsBeastBoardPieceMstByBeastid($beastId) as $bf2BeastBoardPieceMst) {
                // スイッチが立っている場合は変換CP値として、加算
                if(SwitchUtil::isSwitchTrue($bf2UserBeastPieceInfo->getPiece_info(), $bf2BeastBoardPieceMst->getPiece_index())){
                    
                    // 返還ポイント加算
                    $ret += $bf2BeastBoardPieceMst->getCp();
                    
                    // 初期インデックス0は処理スルー
                    if (!StringUtil::isNullOrBlankOrZero($bf2BeastBoardPieceMst->getPiece_index())){
                    
                        // スイッチOFF
                        $bf2UserBeastPieceInfo->updatePieceInfo(STATE_NO, $bf2BeastBoardPieceMst->getPiece_index());
                    }
                }
            }
            
            return $ret;
        }
	}
?>
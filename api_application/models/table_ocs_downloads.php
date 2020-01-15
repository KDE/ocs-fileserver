<?php

/**
 * ocs-fileserver
 *
 * Copyright 2016 by pling GmbH.
 *
 * This file is part of ocs-fileserver.
 *
 * ocs-fileserver is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * ocs-fileserver is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with Foobar.  If not, see <http://www.gnu.org/licenses/>.
 **/

class table_ocs_downloads extends BaseModel
{

    public function __construct(&$db)
    {
        parent::__construct($db, $db->getTableConfig());
        $this->setName('stat_file_downloads');
        $this->setPrimaryInsert(true);
    }

    public function __set($key, $value)
    {
        $value = $this->_convertArrayToObject($value);
        parent::__set($key, $value);
    }

    public function save($value)
    {
        $ipRemoteV6 = filter_var($this->_getIp(), FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) ? $this->_getIp() : null;
        $ipRemoteV4 = filter_var($this->_getIp(), FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) ? $this->_getIp() : null;

        $ipClientv6 = filter_var($value['ip'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) ? $value['ip'] : $ipRemoteV6;
        $ipClientv4 = filter_var($value['ip'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) ? $value['ip'] : $ipRemoteV4;

        $sql = ("INSERT IGNORE INTO `stat_object_download` (`seen_at`, `ip_inet`, `object_type`, `object_id`, `ipv4`, `ipv6`, `fingerprint`, `user_agent`, `member_id_viewer`) VALUES (:seen, :ip_inet, :object_type, :product_id, :ipv4, :ipv6, :fp, :ua, :member)");
        $ip_inet = isset($value['ip']) ? $value['ip'] : $this->_getIp();
        $time = (round(time() / 300)) * 300;
        $seen_at = date('Y-m-d H:i:s', $time);

        $this->_db->addStatementLog($sql);
        $stmt = $this->getDb()->prepare($sql);
        $stmt->execute(array(
            'seen'        => $seen_at,
            'ip_inet'     => inet_pton($ip_inet),
            'object_type' => 40,
            'product_id'  => $value['file_id'],
            'ipv6'        => $ipClientv6,
            'ipv4'        => $ipClientv4,
            'fp'          => $value['fp'] ? $value['fp'] : null,
            'ua'          => $_SERVER['HTTP_USER_AGENT'] ? $_SERVER['HTTP_USER_AGENT'] : null,
            'member'      => isset($vaue['u']) ? $value['u'] : null
        ));
        $stmt->closeCursor();
        $stmt = null;
    }

}

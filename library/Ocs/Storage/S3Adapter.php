<?php
/**
 * file server - part of Opendesktop.org platform project <https://www.opendesktop.org>.
 *
 * Copyright (c) 2016 pling GmbH.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

namespace Ocs\Storage;

use Aws\Credentials\Credentials;
use Aws\S3\S3Client;
use finfo;

class S3Adapter implements AdapterInterface
{
    private object $appConfig;
    private S3Client $s3Client;

    public function __construct(object $appConfig)
    {
        $this->appConfig = $appConfig;
    }

    /**
     * @inheritDoc
     */
    public function moveUploadedFile(string $from, string $to): bool
    {
        error_log("from=$from, to=$to");
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $type = $finfo->file($from);
        $md5sum = md5_file($from);

        $bucketKey = $this->getBucketKey($to);

        $awsClient = $this->getAwsClient();
        $awsResult = $awsClient->putObject(['Bucket'      => $this->appConfig->awss3['bucket'],
                                            'Key'         => $bucketKey,
                                            'SourceFile'  => $from,
                                            'ACL'         => 'private',
                                            'ContentType' => $type,//            'ContentMD5' => base64_encode($md5sum),
                                           ]);

        if (is_object($awsResult)) {
            if ($awsResult->get('@metadata')['statusCode'] != 200) {
                error_log(print_r($awsResult, true));

                return false;
            }
            if (trim($awsResult->get('ETag'), '"') != $md5sum) {
                error_log(__METHOD__ . ' - upload failure: checksum different');
                error_log(print_r($awsResult, true));

                return false;
            }
        }
        error_log(print_r($awsResult, true));

        return true;
    }

    /**
     * @param string $to
     *
     * @return string
     */
    public function getBucketKey(string $to): string
    {
        $bucketPathPrefix = $this->appConfig->awss3['bucketPathPrefix'];
        $pos = strpos($to, $bucketPathPrefix);

        return ltrim(substr($to, $pos), " /");
    }

    private function getAwsClient(): S3Client
    {
        if (!empty($this->s3Client)) {
            return $this->s3Client;
        }
        // Instantiate an Amazon S3 client.
        if (empty($this->appConfig->awss3['endpoint'])) {
            $this->s3Client = new S3Client(['credentials' => new Credentials($this->appConfig->awss3['key'], $this->appConfig->awss3['secret']),
                                            'version'     => 'latest',
                                            'region'      => $this->appConfig->awss3['region'],]);
        } else {
            $this->s3Client = new S3Client(['credentials' => new Credentials($this->appConfig->awss3['key'], $this->appConfig->awss3['secret']),
                                            'version'     => 'latest',
                                            'region'      => $this->appConfig->awss3['region'],
                                            'endpoint'    => $this->appConfig->awss3['endpoint'],]);
        }

        return $this->s3Client;
    }

    public function fixFilename(string $name, string $collectionName): string
    {
        $awsClient = $this->getAwsClient();
        $counter = 0;
        while ($counter < 5) {
            $bucketName = $this->appConfig->awss3['bucketPathPrefix'] . '/' . $collectionName . '/' . $name;
            $awsObjectExists = $awsClient->doesObjectExist($this->appConfig->awss3['bucket'], $bucketName);
            if (!$awsObjectExists) {
                break;
            }
            $counter++;
            $fix = date('YmdHis');
            if (preg_match("/^([^.]+)(\..+)/", $name, $matches)) {
                $name = $matches[1] . '-' . $fix . $matches[2];
            } else {
                $name = $name . '-' . $fix;
            }
        }

        return $name;
    }

    public function prepareCollectionPath(string $collectionName): bool
    {
        return true;
    }

    public function testAndCreate(string $dir): bool
    {
        return true;
    }

    public function moveFile($from, $to): bool
    {
        $awsClient = $this->getAwsClient();
        $destKey = $this->getBucketKey($to);
        $srcKey = $this->getBucketKey($from);
//        $awsResult = $awsClient->headObject([
//            'Bucket'     => $this->appConfig->awss3['bucket'],
//            'Key'        => $srcKey,
//        ]);
        $awsResult = $awsClient->copyObject(['Bucket'     => $this->appConfig->awss3['bucket'],
                                             'Key'        => $destKey,
                                             'CopySource' => $this->appConfig->awss3['bucket'] . '/' . $srcKey,]);
        error_log(__METHOD__ . ' - ' . 'copyObject: ' . print_r($awsResult, true));
        if (is_object($awsResult)) {
            $responseCode = $awsResult->get('@metadata')['statusCode'];
            if (false == in_array($responseCode, [200, 201, 202, 204])) {
                error_log(print_r($awsResult, true));

                return false;
            }
        }

        $awsResult = $awsClient->deleteObject(['Bucket' => $this->appConfig->awss3['bucket'],
                                               'Key'    => $srcKey,]);
        error_log(__METHOD__ . ' - ' . 'copyObject: ' . print_r($awsResult, true));

        return true;
    }

    public function copyFile($from, $to): bool
    {
        $awsClient = $this->getAwsClient();
        $destKey = $this->getBucketKey($to);
        $srcKey = $this->getBucketKey($from);
        $awsResult = $awsClient->copyObject(['Bucket'     => $this->appConfig->awss3['bucket'],
                                             'Key'        => $destKey,
                                             'CopySource' => $this->appConfig->awss3['bucket'] . '/' . $srcKey,]);
        error_log(__METHOD__ . ' - ' . 'copyObject: ' . print_r($awsResult, true));
        if (is_object($awsResult)) {
            $responseCode = $awsResult->get('@metadata')['statusCode'];
            if (false == in_array($responseCode, [200, 201, 202, 204])) {
                error_log(print_r($awsResult, true));

                return false;
            }
        }
        error_log(__METHOD__ . ' - ' . 'copyObject: ' . print_r($awsResult, true));

        return true;
    }

}
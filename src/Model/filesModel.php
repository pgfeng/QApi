<?php
/**
 * Created by PhpStorm.
 * User: PGF
 * Date: 2016/6/2
 * Time: 19:17
 */

namespace QApi\Model;

use QApi\App;
use QApi\Data;
use QApi\Model;
use QApi\Router;
use Symfony\Component\Mime\MimeTypes;


/**
 * 数据库结构
 * DROP TABLE IF EXISTS `{$table_pre}files`;
 * CREATE TABLE `{$table_pre}_files` (
 * `file_id` int(11) NOT NULL AUTO_INCREMENT,
 * `file_name` varchar(255) NOT NULL DEFAULT '' COMMENT '文件名称',
 * `file_size` bigint(20) DEFAULT NULL COMMENT '文件大小',
 * `file_ext` varchar(15) DEFAULT NULL COMMENT '文件后缀名',
 * `file_type` varchar(100) DEFAULT NULL COMMENT '文件类型',
 * `file_md5` varchar(32) DEFAULT NULL COMMENT '文件md5值',
 * `file_time` int(10) DEFAULT NULL COMMENT '创建时间（上传时间）',
 * `file_path` varchar(512) DEFAULT NULL COMMENT '文件路径',
 * PRIMARY KEY (`file_id`),
 * UNIQUE KEY `file_md5` (`file_md5`)
 * ) ENGINE=MyISAM AUTO_INCREMENT=9 DEFAULT CHARSET=utf8;
 */

/**
 *
 * 零操作文件上传模型
 * Class filesModel
 * @package Model
 */
class filesModel extends Model
{

    /**
     * 删除文件
     * @param $file_id
     * @return boolean
     */
    public function del($file_id): bool
    {
        $file = $this->Where('file_id', $file_id)->getOne();
        if ($file) {
            $path = App::$uploadDir . DIRECTORY_SEPARATOR . $file['file_path'];
            if (file_exists($path)) {
                @unlink($path);
            }
            return $this->Where('file_id', $file_id)->delete();
        }
        return TRUE;
    }

    /**
     * 以base64格式上传
     * @param $base64Data
     * @param string[] $allowType
     * @return array
     */
    public function base64_upload($base64Data, array $allowType, string $secondaryDirectory = ''): array
    {
        //--如果没有设置允许格式,将使用默认允许的格式
        foreach ($allowType as &$type) {
            $type = strtolower($type);
        }
        preg_match('/data:(.*);/iUs', $base64Data, $type);
        $mime = $type[1];
        $repository = new MimeTypes();
        $extensions = $repository->getExtensions($mime);
        $data = base64_decode(explode('base64,', $base64Data)[1]);
        if (is_array($extensions)) {
            $ext = $extensions[0];
        } else {
            return [
                'status' => false,
                'msg' => "类型错误，无法上传！"
            ];
        }
        // if (in_array(strtolower($ext), $allowType, true)) {
        $md5 = md5($data);
        if ($file = $this->getFileByMd5($md5)) {
            return [
                'status' => true,
                'path' => $file['file_path'],
                'msg' => '上传成功！！',
            ];
        }
        if ($secondaryDirectory) {
            $path = App::$uploadDir . trim($secondaryDirectory, '/') . '/' . date("Ymd") . '/' . time() . random(10) . '.' . $ext;
        } else {
            $path = App::$uploadDir . date("Ymd") . '/' . time() . random(10) . '.' . $ext;
        }
        $full_path = $path;
        mkPathDir($full_path);
        if (file_put_contents($full_path, $data)) {
            $this->Insert([
                'file_name' => time(),
                'file_size' => strlen($data),
                'file_ext' => $ext,
                'file_type' => $mime,
                'file_md5' => $md5,
                'file_time' => time(),
                'file_path' => $path,
            ]);
            return [
                'status' => true,
                'path' => $path,
                'msg' => '上传成功',
            ];
        } else {
            return [
                'status' => false,
                'msg' => '上传失败',
            ];
        }
        // } else {
        //     return [
        //         'status' => false,
        //         'msg' => '只允许上传' . implode('|', $allowType) . '格式！',
        //     ];
        // }
    }

    /**
     * 验证格式
     * @param $file
     * @param $allow_type
     * @return array
     */
    public function checkType($file, $allow_type): array
    {
        /**
         * 验证包含后缀名的文件
         */
        $extends = explode('.', $file['name']);
        $size = count($extends);
        if ($size > 1) {
            $ext = $extends[$size - 1];
            if (in_array($ext, $allow_type, true)) {
                return [
                    'status' => true,
                    'msg' => '效验成功',
                    'ext' => $ext,
                ];
            }
        }
        /**
         * 验证不含后缀名的文件
         */
        $repository = new MimeTypes();
        $extensions = $repository->getExtensions($file['type']);
        if (count($extensions) === 0) {
            return [
                'status' => false,
                'msg' => "未知的MIME类型，" . $file['type'],
            ];
        }

        foreach ($extensions as $ext) {
            if (in_array(strtolower($ext), $allow_type, true)) {
                return [
                    'status' => true,
                    'msg' => "效验成功",
                    'ext' => $ext
                ];
            }
        }
        return [
            'status' => false,
            'msg' => '您上传的文件格式有误！'
        ];
    }

    /**
     * 上传文件,返回文件保存路径,如果是数组形式的，那么返回的也是数组形式的，结构和单文件相同
     * @param $field_file
     * @param string[] $allow_type
     * @return array
     */
    public function upload($fieldFile, array $allowType, string $secondaryDirectory = ''): array
    {
        //--如果没有设置允许格式,将使用默认允许的格式
        foreach ($allowType as &$type) {
            $type = strtolower($type);
        }
        $file = $fieldFile;
        if (!isset($file['error'])) {
            return [
                'status' => false,
                'msg' => '传入参数有误,请联系平台技术人员!',
            ];
        }
        if (is_array($file['error'])) {
            $files = [];
            foreach ($file['error'] as $key => $error) {
                $f = [];
                if ((int)$error === 0 && (int)$file['size'][$key] !== 0) {
                    $md5 = md5_file($file['tmp_name'][$key]);
                    if ($file = $this->getFileByMd5($md5)) {
                        $f = [
                            'status' => true,
                            'path' => $file['file_path'],
                            'msg' => '上传成功！！',
                        ];
                    } else {
                        $result = $this->checkType($file, $allowType);
                        if ($result['status']) {
                            $ext = $result['ext'];
                        } else {
                            return $result;
                        }
                        if ($secondaryDirectory == '') {
                            $path = App::$uploadDir . date("Ymd") . DIRECTORY_SEPARATOR . time() . random(10) . '.' . $ext;
                        } else {
                            $path = App::$uploadDir . trim($secondaryDirectory) . DIRECTORY_SEPARATOR . date("Ymd") . DIRECTORY_SEPARATOR . time() . random(10) . '.' . $ext;
                        }
                        if (Router::$request) {
                            $full_path = Router::$request->server->get('DOCUMENT_ROOT') . DIRECTORY_SEPARATOR . $path;
                        } else {
                            $full_path = $path;
                        }
                        mkPathDir($full_path);
                        if (@move_uploaded_file($file['tmp_name'][$key], $full_path) || @rename($file['tmp_name'][$key], $full_path)) {
                            $this->Insert([
                                'file_name' => $file['name'][$key],
                                'file_size' => $file['size'][$key],
                                'file_ext' => $ext,
                                'file_type' => $file['type'][$key],
                                'file_md5' => $md5,
                                'file_time' => time(),
                                'file_path' => str_replace(DIRECTORY_SEPARATOR, '/', $path),
                            ]);
                            $f = [
                                'status' => true,
                                'path' => $path,
                                'msg' => '上传成功',
                            ];
                        } else {
                            return [
                                'status' => false,
                                'msg' => "系统故障，请稍后重试!"
                            ];
                        }

                    }
                } else {
                    $f = [
                        'status' => false,
                        'msg' => $this->getErrorMsg($error),
                    ];
                }
                $files[] = $f;
            }

            return $files;
        }

        if ((int)$file['error'] === 0 && (int)$file['size'] !== 0) {
            $md5 = md5_file($file['tmp_name']);
            if ($rfile = $this->getFileByMd5($md5)) {
                return [
                    'status' => true,
                    'path' => $rfile['file_path'],
                    'msg' => '上传成功！！',
                ];
            }
            $result = $this->checkType($file, $allowType);
            if ($result['status']) {
                $ext = $result['ext'];
            } else {
                return $result;
            }
            $path = App::$uploadDir . date("Ymd") . DIRECTORY_SEPARATOR . time() . random(10) . '.' . $ext;
            if (Router::$request) {
                $full_path = Router::$request->server->get('DOCUMENT_ROOT') . DIRECTORY_SEPARATOR . $path;
            } else {
                $full_path = $path;
            }
            mkPathDir($full_path);
            if (@move_uploaded_file($file['tmp_name'], $full_path) || @rename($file['tmp_name'], $full_path)) {
                $this->Insert([
                    'file_name' => $file['name'],
                    'file_size' => $file['size'],
                    'file_ext' => $ext,
                    'file_type' => $file['type'],
                    'file_md5' => $md5,
                    'file_time' => time(),
                    'file_path' => str_replace(DIRECTORY_SEPARATOR, '/', $path),
                ]);

                return [
                    'status' => true,
                    'path' => $path,
                    'msg' => '上传成功',
                ];
            }

            return [
                'status' => false,
                'msg' => '系统故障，请稍后重试',
            ];
        }

        return [
            'status' => false,
            'msg' => $this->getErrorMsg($file['error']),
        ];
    }

    /**
     * 根据错误码获取错误
     * @param $error_code
     * @return string
     */
    public function getErrorMsg($error_code): string
    {
        switch ($error_code) {
            case NULL:
                $error_msg = '没有上传！';
                break;
            case 1:
                $error_msg = '超过了配置中限制的值！';
                break;
            case 2:
                $error_msg = '超过了表单中限制的值！';
                break;
            case 3:
                $error_msg = '没有完整上传！';
                break;
            case 4:
                $error_msg = '没有文件上传！';
                break;
            case 5:
                $error_msg = '找不到临时文件夹！';
                break;
            case 6:
                $error_msg = '临时文件写入失败！';
                break;
            default:
                $error_msg = '出现未知错误！';

        }

        return $error_msg;
    }

    /**
     * 用MD5值获取文件
     * @param $md5
     * @param bool $all
     * @return Data|null
     */
    public function getFileByMd5($md5, bool $all = FALSE): Data|null
    {
        $this->Where('file_md5', $md5);
        if ($all) {
            return $this->getOne();
        }

        return $this->getOne('file_path');
    }

    /**
     * @param $file_id
     * @param bool $all
     * @return Data|null
     */
    public function getFileById($file_id, bool $all = FALSE): Data|null
    {
        $this->Where('file_id', $file_id);
        if ($all) {
            return $this->getOne();
        }

        return $this->getOne('file_path');
    }
}
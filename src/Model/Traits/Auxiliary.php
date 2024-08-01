<?php


namespace QApi\Model\Traits;

use QApi\Data;
use QApi\Model;
use QApi\Request;
use QApi\Response;

/**
 * 模型辅助包
 * Trait AutoSave
 * @mixin Model|\QApi\ORM\Model
 * @package QApi\Model\Traits
 */
trait Auxiliary
{

    /**
     * 是否保存操作时间
     * @var bool
     */
    protected bool $autoSaveTime = true;

    /**
     * 创建时间字段
     * @var string
     */
    protected string $insertTimeField = 'insert_time';

    /**
     * 修改时间字段
     * @var string
     */
    protected string $updateTimeField = 'update_time';

    /**
     * 当前操作的内容
     *
     * @var string
     */
    protected string $modelName = '';

    /**
     * 修改文本描述
     * @var string
     */
    protected string $editString = '修改';

    /**
     * 修改文本描述
     * @var string
     */
    protected string $deleteString = '删除';

    /**
     * 添加文本描述
     * @var string
     */
    protected string $addString = '添加';

    /**
     * 操作成功提示文本
     * @var string
     */
    protected string $successString = '成功了！';

    /**
     * 操作失败提示文本
     * @var string
     */
    protected string $errorString = '失败，请重试！';

    /**
     * 设置是否保存时间
     * @param bool $autoSaveTime
     * @return self
     */
    final public function saveTime(bool $autoSaveTime = true): self
    {
        $this->autoSaveTime = $autoSaveTime;
        return $this;
    }

    /**
     * 自动保存数据，返回Response
     * @param Data|array $data
     * @param false|string $primary_key
     * @param Response|null $response
     * @return Response
     */
    public function autoSave(Data|array $data, false|string $primary_key = false, ?Response $response = null): Response
    {
        !$primary_key && $primary_key = $this->primary_key;
        if ($response === null) {
            $response = new Response();
        }
        if (method_exists($this, 'checkColumn')) {
            $message = $this->checkColumn($data);
            if ($message) {
                return $response->fail()->setMsg($message);
            }
        }

        if (!defined('__NOW__')) {
            $time = date('Y-m-d H:i:s');
        } else {
            $time = date('Y-m-d H:i:s', __NOW__);
        }

        if (isset($data[$primary_key]) && $data[$primary_key]) {
            $handle = $this->editString;
            if ($this->autoSaveTime && $this->updateTimeField) {
                $data[$this->updateTimeField] = $data[$this->autoSaveTime] ?? $time;
            }
        } else {
            $handle = $this->addString;
            if (isset($data[$primary_key]))
                unset($data[$primary_key]);
            if ($this->autoSaveTime) {
                if ($this->insertTimeField) {
                    $data[$this->insertTimeField] = $data[$this->insertTimeField] ?? $time;
                }
                if ($this->updateTimeField) {
                    $data[$this->updateTimeField] = $data[$this->updateTimeField] ?? $time;
                }
            }
        }
        $status = $this->save($data, $primary_key);
        if ($status !== false) {
            if ($primary_key) {
                if (!isset($this->lastInsertUUID)) {
                    if (isset($data[$primary_key])) {
                        $response->setExtra([$primary_key => $data[$primary_key], 'id' => $data[$primary_key]]);
                    } else {
                        $id = $this->lastInsertId();
                        $response->setExtra([$primary_key => $id, 'id' => $id]);
                    }
                } else {
                    if (isset($data[$primary_key])) {
                        $response->setExtra([$primary_key => $data[$primary_key], 'UUID' => $data[$primary_key]]);
                    } else {
                        $response->setExtra([$primary_key => $this->lastInsertUUID, 'UUID' => $this->lastInsertUUID]);
                    }
                }
            }
            return $response->success()->setMsg($this->modelName . $handle . $this->successString);
        }

        return $response->fail()->setMsg($this->modelName . $handle . $this->errorString);
    }

    /**
     * 获取列表数据
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function toTableData(Request $request, Response $response): Response
    {
        $sortField = $request->request->get('sortField');
        $sortOrder = $request->request->get('sortOrder');
        $page = $request->request->get('page', 1);
        $size = $request->request->get('size', 10);
        if ($sortField) {
            $this->orderBy($sortField, $sortOrder === 'ascend' ? 'asc' : 'desc');
        }
        $countModel = clone $this;
        return $response->success()->setExtra([
            'total' => $countModel->count(),
        ])->setData($this->paginate($size, $page));
    }

    /**
     * 自动删除
     * @param Response|null $response
     * @return Response
     */
    public function autoDelete(?Response $response = null, bool $softDelete = true): Response
    {
        if (!$response) {
            $response = new Response();
        }
        if (method_exists($this, 'softDelete')) {
            if ($this->softDelete($softDelete) !== false) {
                return $response->success()->setMsg($this->modelName . $this->deleteString . $this->successString);
            }
        } else {
            if ($this->delete() !== false) {
                return $response->success()->setMsg($this->modelName . $this->deleteString . $this->successString);
            }
        }
        return $response->fail()->setMsg($this->modelName . $this->deleteString . $this->errorString);
    }

}
<?php
/**
 * 汇聚层
 * 集中管理Transfer和Business的在线/离线状态。提供离线踢出, 上线推送等功能。
 * Created by lixuan-it@360.cn
 * User: lane
 * Date: 16/6/29
 * Time: 下午3:20
 * E-mail: lixuan868686@163.com
 * WebSite: http://www.lanecn.com
 */
namespace MeepoPS\Core\ThreeLayerMould;

use MeepoPS\Core\MeepoPS;

class Business extends MeepoPS{

    public function __construct()
    {
        parent::__construct();
    }
}
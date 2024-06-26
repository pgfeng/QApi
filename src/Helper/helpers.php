<?php


use Composer\Autoload\ClassLoader;
use JetBrains\PhpStorm\Deprecated;
use JetBrains\PhpStorm\Pure;

/**
 * 获取一个全局唯一键值
 * @return string
 */
function buildID(): string
{
    return \Symfony\Component\Uid\Ulid::generate();
}

/**
 * 排序数组
 * @param array $list
 * @param string $field
 * @param string $sort
 * @return array
 */
function listSortBy(array $list, string $field, string $sort = 'asc'): array
{
    $refer = $resultSet = array();
    foreach ($list as $i => $data)
        $refer[$i] = $data[$field];
    switch ($sort) {
        case 'asc': // 正向排序
            asort($refer);
            break;
        case 'desc': // 逆向排序
            arsort($refer);
            break;
        case 'nat': // 自然排序
            natcasesort($refer);
            break;
    }
    foreach ($refer as $key => $val)
        $resultSet[] = &$list[$key];
    return $resultSet;
}
/**
 * 获取命名空间
 * @param $nameSpace
 * @return string
 */
function getComposerNameSpaceDir($nameSpace): string
{
    $load = ClassLoader::getRegisteredLoaders();
    $vendorDir = PROJECT_PATH . 'vendor';
    foreach ($load as $item) {
        preg_match('/vendorDir".*"(.*)"/iUs', serialize($item), $matches);
        if (count($matches) == 2) {
            $vendorDir = $matches[1];
            break;
        }
    }
    $psr4 = include $vendorDir . DIRECTORY_SEPARATOR . 'composer' . DIRECTORY_SEPARATOR . 'autoload_psr4.php';
    $psr4Dir = [];
    foreach ($psr4 as $space => $dirArray) {
        $in = false;
        foreach ($dirArray as $dir) {
            if (str_starts_with($dir, $vendorDir)) {
                $in = true;
                break;
            }
        }
        if (!$in) {
            $psr4Dir[$space] = end($dirArray);
        }
    }
    return $psr4Dir[$nameSpace];
}

/**
 * 蛇形命名转换为驼峰命名
 */
function convertToCamelCase(string $name): string
{
    $name = strtolower($name);
    $name = preg_replace_callback('/_([a-z])/', function ($match) {
        return strtoupper($match[1]);
    }, $name);
    return ucfirst($name);
}


/**
 * 大驼峰转换为蛇形命名
 */
function convertToSnakeCase(string $name): string
{
    $name = preg_replace('/[A-Z]/', '_$0', $name);
    return strtolower(trim($name, "_"));
}

/**
 * 获取一个全局唯一键值
 * @param int $length
 * @param Closure|null $filter
 * @return string
 */
function randomOpenSSL(int $length = 6, Closure|null $filter = null): string
{
    $bytes = openssl_random_pseudo_bytes(ceil($length / 2));
    $id = bin2hex($bytes);
    if ($filter) {
        return $filter($id);
    } else {
        return strtoupper($id);
    }
}

/**
 * @param $func
 * @return string
 */
function closureToStr($func): string
{
    $refl = new \ReflectionFunction($func); // get reflection object
    $path = $refl->getFileName();  // absolute path of php file
    $begn = $refl->getStartLine(); // have to `-1` for array index
    $endn = $refl->getEndLine();
    $dlim = "\n"; // or PHP_EOL
    $list = explode($dlim, file_get_contents($path));         // lines of php-file source
    $list = array_slice($list, ($begn - 1), ($endn - ($begn - 1))); // lines of closure definition
    $last = (count($list) - 1); // last line number

    if ((substr_count($list[0], 'function') > 1) || (substr_count($list[0], '{') > 1) || (substr_count($list[$last], '}') > 1)) {
        throw new \Exception("Too complex context definition in: `$path`. Check lines: $begn & $endn.");
    }

    $list[0] = ('function' . explode('function', $list[0])[1]);
    $list[$last] = (explode('}', $list[$last])[0] . '}');

    return implode($dlim, $list);
}

/**
 * 解析正确路径
 *
 * @return string
 */
function parseDir(): string
{
    $dirs = func_get_args();
    $dir = '';
    foreach ($dirs as $d) {
        $d = trim($d);
        if ($d !== '') {
            if ((string)$d[0] === '/') {
                $d = substr($d, 1);
            }
            if ($d[strlen($d) - 1] !== '/') {
                $d .= '/';
            }
            $dir .= $d;
        }
    }
    $dir = explode('/', $dir);
    foreach ($dir as $i => $iValue) {
        if ($iValue === '') {
            continue;
        }
        if ((str_contains('..', $iValue)) && ($i > 0)) {
            unset($dir[$i - 1], $dir[$i]);
        }
    }

    return implode('/', $dir);
}

/**
 * 字符截取
 *
 * @param        $string
 * @param        $length
 * @param string $dot
 *
 * @return string
 */
function str_cut($string, $length, string $dot = '...'): string
{
    $length = (int)$length;
    //--将html标签剔除
    $string = strip_tags($string);
    //--获取内容长度
    $str_len = mb_strlen($string, 'utf8');
    //--如果没有超过直接返回
    if ($str_len <= $length) {
        return $string;
    }

    //    $string = str_replace([' ', ' ', '&amp;', '"', '&#039;', '&ldquo;', '&rdquo;', '&mdash;', '<', '>', '&middot;', '&hellip;'], ['', '',
    //        '&', '"', "'", '&ldquo;', '&rdquo;', '&mdash;', '<', '>', '&middot;', '&hellip;'], $string);
    //    $string = preg_replace("/<\/?[^>]+>/i", '', $string);
    $str_cut = mb_substr($string, 0, $length, 'utf-8');

    return $str_cut . $dot;
}

/**
 * CLI模式运行
 * @return bool
 */
#[Pure] function is_cli(): bool
{
    return (strtoupper(PHP_SAPI) === 'CLI');
}

/**
 * CLI-SERVER 模式运行
 * @return bool
 */
#[Pure] function is_cli_server(): bool
{
    return (strtoupper(PHP_SAPI) === 'CLI-SERVER');
}


/**
 * 创建文件所在目录
 *
 * @param     $path
 * @param int $mode
 *
 * @return bool
 */
function mkPathDir($path, $mode = 0777): bool
{
    $dir = dirname($path);
    if (!is_dir($dir)) {
        if (!file_exists($dir)) {
            return mkdir($dir, $mode, TRUE);
        }

        return FALSE;
    }

    return TRUE;
}

/**
 * 人性化的时间显示
 *
 * @param String|null $time Unix时间戳，默认为当前时间
 * @param string $date_format 默认时间显示格式
 *
 * @return String
 */
function toTime(string $time = NULL, string $date_format = 'Y/m/d H:i:s'): string
{
    $time ??= time();
    $now = time();
    $diff = $now - $time;
    if ($diff < 10) {
        return '刚刚 ';
    }
    if ($diff < 60) {
        return $diff . '秒前 ';
    }
    if ($diff < (60 * 60)) {
        return floor($diff / 60) . '分钟前 ';
    }
    if (date('Ymd', $time) === date('Ymd', $now)) {
        return '今天 ' . date('H:i:s', $time);
    }

    return date($date_format, $time);
}

/**
 * 文件大小单位换算
 *
 * @param int $byte 文件Byte值
 *
 * @return String
 */
#[Pure] function toSize(int $byte): string
{
    if ($byte >= (2 ** 40)) {
        $return = round($byte / (1024 ** 4), 2);
        $suffix = "TB";
    } elseif ($byte >= (2 ** 30)) {
        $return = round($byte / (1024 ** 3), 2);
        $suffix = "GB";
    } elseif ($byte >= (2 ** 20)) {
        $return = round($byte / (1024 ** 2), 2);
        $suffix = "MB";
    } elseif ($byte >= (2 ** 10)) {
        $return = round($byte / (1024 ** 1), 2);
        $suffix = "KB";
    } else {
        $return = $byte;
        $suffix = "Byte";
    }

    return $return . " " . $suffix;
}

/**
 * xss过滤函数 --来自PHPCMS
 *
 * @param $string
 *
 * @return string
 */
function remove_xss($string): string
{
    $string = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]+/S', '', $string);

    $params_1 = ['javascript', 'vbscript', 'expression', 'applet', 'meta', 'xml', 'blink', 'link', 'script', 'embed',
        'object', 'iframe', 'frame', 'frameset', 'ilayer', 'layer', 'bgsound', 'title', 'base'];

    $params_2 = ['onabort', 'onactivate', 'onafterprint', 'onafterupdate', 'onbeforeactivate', 'onbeforecopy', 'onbeforecut', 'onbeforedeactivate', 'onbeforeeditfocus', 'onbeforepaste', 'onbeforeprint', 'onbeforeunload', 'onbeforeupdate', 'onblur', 'onbounce', 'oncellchange', 'onchange', 'onclick', 'oncontextmenu', 'oncontrolselect', 'oncopy', 'oncut', 'ondataavailable', 'ondatasetchanged', 'ondatasetcomplete', 'ondblclick', 'ondeactivate', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onerror', 'onerrorupdate', 'onfilterchange', 'onfinish', 'onfocus', 'onfocusin', 'onfocusout', 'onhelp', 'onkeydown', 'onkeypress', 'onkeyup', 'onlayoutcomplete', 'onload', 'onlosecapture', 'onmousedown', 'onmouseenter', 'onmouseleave', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onmove', 'onmoveend', 'onmovestart', 'onpaste', 'onpropertychange', 'onreadystatechange', 'onreset', 'onresize', 'onresizeend', 'onresizestart', 'onrowenter', 'onrowexit', 'onrowsdelete', 'onrowsinserted', 'onscroll', 'onselect', 'onselectionchange', 'onselectstart', 'onstart', 'onstop', 'onsubmit', 'onunload'];

    $params = array_merge($params_1, $params_2);

    foreach ($params as $i => $iValue) {
        $pattern = '/';
        for ($j = 0, $jMax = strlen($iValue); $j < $jMax; $j++) {
            if ($j > 0) {
                $pattern .= '(';
                $pattern .= '(&#[x|X]0([9][a][b]);?)?';
                $pattern .= '|(&#0([9][10][13]);?)?';
                $pattern .= ')?';
            }
            $pattern .= $params[$i][$j];
        }
        $pattern .= '/i';
        $string = preg_replace($pattern, '', $string);
    }

    return $string;
}

/**
 * @param $closure
 * @return string
 */
function closure_dump($closure): string
{
    try {
        $func = new ReflectionFunction($closure);
    } catch (ReflectionException $e) {
        echo $e->getMessage();
        return '';
    }

    $start = $func->getStartLine() - 1;

    $end = $func->getEndLine() - 1;

    $filename = $func->getFileName();

    return implode("", array_slice(file($filename), $start, $end - $start + 1));
}

/**
 * 函数来源 ThinkPhp
 *
 * @param      $var
 * @param bool $echo
 * @param null $label
 * @param bool $strict
 *
 * @return null|bool|string
 */
function dump($var, bool $echo = TRUE, $label = NULL, bool $strict = TRUE): null|bool|string
{
    $label = ($label === NULL) ? '' : rtrim($label) . ' ';
    if (!$strict) {
        if (ini_get('html_errors')) {
            $output = print_r($var, TRUE);
            $output = "<pre>" . $label . htmlspecialchars($output, ENT_QUOTES) . "</pre>";
        } else {
            $output = $label . print_r($var, TRUE);
        }
    } else {
        ob_start();
        var_dump($var);
        $output = ob_get_clean();
        if (!extension_loaded('xdebug')) {
            $output = preg_replace("/\]\=\>\n(\s+)/m", "] => ", $output);
            $output = '<pre>' . $label . htmlspecialchars($output, ENT_QUOTES) . '</pre>';
        }
    }
    if ($echo) {
        echo($output);

        return NULL;
    }

    return $output;
}


/**
 * 产生随机字符串
 *
 * @param int $length 输出长度
 * @param string $chars 可选的 ，默认为 0123456789
 *
 * @return   string     字符串
 */
function random(int $length, string $chars = '0123456789'): string
{
    $hash = '';
    $max = strlen($chars) - 1;
    for ($i = 0; $i < $length; $i++) {
        $hash .= $chars[random_int(0, $max)];
    }

    return $hash;
}


/**
 * XML编码
 *
 * @param mixed $data 数据
 * @param string $root 根节点名
 * @param string $item 数字索引的子节点名
 * @param string $attr 根节点属性
 * @param string $id 数字索引子节点key转换的属性名
 * @param string $encoding 数据编码
 *
 * @return string
 */
function xml_encode(mixed $data, string $root = 'root', string $item = 'item', string $attr = '', string $id = 'id', string $encoding = 'utf-8'): string
{
    if (is_array($attr)) {
        $_attr = [];
        foreach ($attr as $key => $value) {
            $_attr[] = "{$key}=\"{$value}\"";
        }
        $attr = implode(' ', $_attr);
    }
    $attr = trim($attr);
    $attr = empty($attr) ? '' : " {$attr}";
    $xml = "<?xml version=\"1.0\" encoding=\"{$encoding}\"?>";
    $xml .= "<{$root}{$attr}>";
    $xml .= data_to_xml($data, $item, $id);
    $xml .= "</{$root}>";

    return $xml;
}

/**
 * 数据XML编码
 *
 * @param array $data 数据
 * @param string $item 数字索引时的节点名称
 * @param string $id 数字索引key转换为的属性名
 *
 * @return string
 */
function data_to_xml(array $data, string $item = 'item', string $id = 'id'): string
{
    $xml = $attr = '';
    foreach ($data as $key => $val) {
        if (is_numeric($key)) {
            $id && $attr = " {$id}=\"{$key}\"";
            $key = $item;
        }
        $xml .= "<{$key}{$attr}>";
        $xml .= (is_array($val) || is_object($val)) ? data_to_xml($val, $item, $id) : $val;
        $xml .= "</{$key}>";
    }

    return $xml;
}





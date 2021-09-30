<?php

/*
 * What SameGo team is that is 'one thing, a team, work together'
 * @copyright Copyright (c) 2021, SameGo All Rights Reserved.
 */

// 1.load configuration
$config = parse_ini_file(__DIR__ . '/../config/' . 'env.ini', true);

// 2.create websocket server
$server = new swoole_websocket_server($config['server']['host'], $config['server']['port']);

// open callback function
$server->on(
    'open',
    function (swoole_websocket_server $server, $request) {
        // connected successful, then record
        echo "$request->fd connected...\n";
    }
);

// message callback function
$server->on(
    'message',
    function (swoole_websocket_server $server, $frame) use (&$config) {
        // frame construct : frame.fd frame.data frame.opcode frame.finish
        // 获取日志的命名空间
        $log_namespace = $frame->data;
        echo "recv $log_namespace from $frame->fd\n";

        // 判断是否已经成功建立连接 否则就不处理了
        if (false === $server->isEstablished($frame->fd)) {
            return true;
        }

        // 校验参数是否按照约定传输 | 日志的命名空间
        if (empty($log_namespace) || !is_string($log_namespace)) {
            $server->push($frame->fd, 'package params error');

            return true;
        }

        // 拼接日志文件的路径 并 判断日志文件是否存在
        $log_path = $config['setting']['volume_dir'] . $log_namespace . $config['setting']['base_path'];
        if (false === file_exists($log_path)) {
            $server->push($frame->fd, 'log file not exist : ' . $log_path);

            return true;
        }

        // 计算日志文件大小
        $file_size = filesize($log_path);
        $fp        = fopen($log_path, 'r');
        // 处理默认显示信息关系 | 即使没有增量也要显示之前一些日志信息出来
        $file_size = $file_size - $config['setting']['display_block'];
        if ($file_size < $config['setting']['display_block']) {
            $file_size = 0;
        }
        fseek($fp, $file_size);

        while (true) {
            // 手动心跳 | 判断客户端是否断开 假设断开则结束进程
            if (false == $server->push($frame->fd, null)) {
                break;
            }
            // 清除文件状态缓存
            clearstatcache();
            // 计算vas日志文件当前大小
            $file_current_size = filesize($log_path);
            // 获取vas日志文件增量单位
            $add_size = $file_current_size - $file_size;
            // 假设文件没有增量那就不读取了同时睡眠下 500ms
            if (0 === $add_size) {
                echo "[$frame->fd] log file not change... " . time() . "\n";
                usleep(1000 * 500);

                continue;
            }
            // 计算增量单位大小
            if ($add_size > $config['setting']['once_block']) {
                $add_size = $config['setting']['once_block'];
            }

            // 读取增量内容
            if ($log_content = fread($fp, $add_size)) {
                $log_content = nl2br($log_content);
                $server->push($frame->fd, $log_content);
            }

            // 文件seek指针移位
            fseek($fp, $file_size + $add_size);
            $file_size = $file_current_size;

            // 时间间隔为1秒
            sleep(1);
        }
        fclose($fp);

        return true;
    }
);

// close callback function
$server->on(
    'close',
    function (swoole_websocket_server $server, $fd) {
        // close session as well as destroy flag fd
        echo "$fd closed...\n";
    }
);

// set server runtime params
$server->set($config['runtime']);

// start server as service
$server->start();

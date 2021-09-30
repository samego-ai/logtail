## 项目

这是项目描述概览.... 先跳过...。用于 **日志查看** 



#### 功能

###### 通知服务

目前仅用于报警推送，包括报警实时推送信息 与 演示中心具体某一算法信息推送

- websocket 服务信息

  ```ini
  host=logtail.default.svc.cluster.local
  port=1280
  ```

###### 日志

日志服务用户读取 `algorithm` 日志内容，通过 `websocket` 通讯传输给客户端实现实时渲染日志内容。

- websocket 服务信息


> 注意：客户端通讯读取日志时，关闭页面、切换日志、关闭浏览器是务必关闭当前进程，倘若需要切换查看其他容器的日志时、请务必开启新的进程。

- 通讯使用说明

  获取具体算法日志请求时、需要传对应的 `k8s部署名称`，示例如下

  ```javascript
  let ws = new WebSocket("ws://logtail.default.svc.cluster.local:1280");
  
  // ... ...
  ws.send('namespace');
  
  // ... ...
  ws.onmessage = function (data) {
      // 在这里我会直接回传日志的信息过来 | data.data
      console.log("收到socket服务消息，内容：" + data.data);
  };
  ```









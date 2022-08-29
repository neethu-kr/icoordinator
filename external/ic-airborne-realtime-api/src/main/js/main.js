(function () {
  'use strict';

  var
    ChannelManager = require('./ChannelManager'),
    ChannelServer = require('./ChannelServer'),
    config = require('./config/config.js'),
    EventBroker = require('./mq/EventBroker'),
    iclog = require('./log/iclog'),
    port = parseInt(process.env.PORT, 10) || 8080,
    redis = require('./config/redis'),
    npm = require('../../../package.json');

  (function main() {
    iclog.info("Starting iCoordinator Real-Time API Service...");
    iclog.info("Version", npm.version);

    var manager, server;

    if (!redis.isConfigValid()) {
      iclog.alert(
        'Incomplete Redis configuration. Configuration expected via ' +
        'environment variables. See src/main/js/config/redis.js for ' +
        'further details.',
        new Error().stack, redis
      );
      process.exit(1);
    }

    manager = new ChannelManager(
      new EventBroker(redis),
      config.channel
    );

    server = new ChannelServer(function (request) {
      manager.delegate(request);
    }, config.request);

    iclog.info("Listening on port", port);
    server.listen(port);
  }());
}());

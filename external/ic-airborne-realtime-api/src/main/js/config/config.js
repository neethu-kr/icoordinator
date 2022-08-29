(function () {
  'use strict';

  /**
   * General application configuration.
   *
   * The configuraiton is altered by providing some or all of the environment
   * variables `RTAPI_CHANNEL_TIMEOUT`, `RTAPI_CHANNEL_RETRY_LIMIT`, and
   * `RTAPI_REQUEST_PING_INTERVAL`.
   *
   * @module config/general
   */
  module.exports = {
    channel: {
      timeout: process.env.RTAPI_CHANNEL_TIMEOUT ||  600,
      retryLimit: process.env.RTAPI_CHANNEL_RETRY_LIMIT || 10
    },
    request: {
      pingInterval: process.env.RTAPI_REQUEST_PING_INTERVAL || 25
    }
  };
}());

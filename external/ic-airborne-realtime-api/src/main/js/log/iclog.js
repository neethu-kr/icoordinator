(function () {
  'use strict';

  function log(level, message, context, extra) {
    var time, channel, other;

    time = (new Date()).toISOString();
    channel = 'ic_api';

    other = '';
    if (message !== undefined) {
      if (typeof message === 'string') {
        other += '"' + message.replace(/[\n\r]/, '') + '" ';
      } else {
        other += JSON.stringify(message) + ' ';
      }
    }
    if (context !== undefined) {
      other += JSON.stringify(context) + ' ';
    }
    if (extra !== undefined) {
      other += JSON.stringify(extra);
    }
    console.log('[%s] %s.%s: %s', time, channel, level, other);
  }

  /**
   * iCoordinator logging utilities.
   *
   * Provides functions for logging to STDOUT with standardized formatting.
   *
   * @module log/iclog
   */
  module.exports = {
    /**
     * Logs emergency level error.
     *
     * @param  {string} message   Emergency description.
     * @param  {Object} [context] Application context. Like `new Error().stack`.
     * @param  {Object} extra     Any other data of interest.
     */
    emergency: function (message, context, extra) {
      log('emergency', message, context, extra);
    },

    /**
     * Logs alert level error.
     *
     * @param  {string} message   Alert description.
     * @param  {Object} [context] Application context. Like `new Error().stack`.
     * @param  {Object} extra     Any other data of interest.
     */
    alert: function (message, context, extra) {
      log('alert', message, context, extra);
    },

    /**
     * Logs critical level error.
     *
     * @param  {string} message   Critical error description.
     * @param  {Object} [context] Application context. Like `new Error().stack`.
     * @param  {Object} extra     Any other data of interest.
     */
    critical: function (message, context, extra) {
      log('critical', message, context, extra);
    },

    /**
     * Logs error.
     *
     * @param  {string} message   Error description.
     * @param  {Object} [context] Application context. Like `new Error().stack`.
     * @param  {Object} extra     Any other data of interest.
     */
    error: function (message, context, extra) {
      log('error', message, context, extra);
    },

    /**
     * Logs warning.
     *
     * @param  {string} message   Warning description.
     * @param  {Object} [context] Application context. Like `new Error().stack`.
     * @param  {Object} extra     Any other data of interest.
     */
    warning: function (message, context, extra) {
      log('warning', message, context, extra);
    },

    /**
     * Logs notice.
     *
     * @param  {string} message   Notice description.
     * @param  {Object} [context] Application context. Like `new Error().stack`.
     * @param  {Object} extra     Any other data of interest.
     */
    notice: function (message, context, extra) {
      log('notice', message, context, extra);
    },

    /**
     * Logs information.
     *
     * @param  {string} message   Information description.
     * @param  {Object} [context] Application context. Like `new Error().stack`.
     * @param  {Object} extra     Any other data of interest.
     */
    info: function (message, context, extra) {
      log('info', message, context, extra);
    },

    /**
     * Logs debug data.
     *
     * @param  {string} message   Debug data description.
     * @param  {Object} [context] Application context. Like `new Error().stack`.
     * @param  {Object} extra     Any other data of interest.
     */
    debug: function (message, context, extra) {
      log('debug', message, context, extra);
    }
  };
}());

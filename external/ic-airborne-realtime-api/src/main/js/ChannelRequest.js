(function () {
  'use strict';

  /**
   * A client channel request.
   *
   * Encapsulates a client HTTP request made in order to attach to some
   * long-polling channel.
   *
   * @param {string} token   ChannelRequest token identifier.
   * @param {Object} query   Query parameters provided by client.
   * @param {Object} control ChannelServer control object.
   */
  function ChannelRequest(token, query, control) {
    var self = this;

    /**
     * ChannelRequest token identifier.
     *
     * @type {string}
     */
    this.token = token;

    /** @private */
    this.query = query || {};

    /** @private */
    this.control = control;

    /** @private */
    this.pinger = setInterval(function () {
      self.ping();
    }, (((control || {}).options || {}).pingInterval || 25) * 1000);
  }

  /**
   * Query parameters provided by client performing request.
   *
   * @return {Object} Map of query parameters.
   */
  ChannelRequest.prototype.queryParameters = function () {
    return this.query;
  };

  /**
   * Sends a ping response to client, without closing the request. This can be
   * used to effectively prolong the lifetime of the request's idle state,
   * which is subject to both the client's and server's network timeout
   * restrictions.
   *
   * Do note, however, that there is usually no reason to explicitly ping a
   * request, as pinging will be set up automaically when the object is
   * created.
   */
  ChannelRequest.prototype.ping = function () {
    this.control.ping();
  };

  /**
   * Responds with notification of given string type.
   *
   * @param {string} type Notification type.
   */
  ChannelRequest.prototype.send = function (type) {
    this.control.send({
      type: type
    });
  };

  /**
   * Responds with given error message, if possible.
   *
   * @param {string} message Human-readable explanation of error.
   */
  ChannelRequest.prototype.sendError = function (message) {
    this.control.send({
      type: 'error',
      message: message
    });
  };

  /**
   * Destroys request, cleaning up resources used.
   *
   * Do note that failing to destroy a request may cause memory leaks.
   */
  ChannelRequest.prototype.destroy = function () {
    if (this.pinger) {
      clearInterval(this.pinger);
      delete this.pinger;
    }
  };

  module.exports = ChannelRequest;
}());

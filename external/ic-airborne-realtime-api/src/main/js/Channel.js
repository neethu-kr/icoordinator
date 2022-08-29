(function () {
  'use strict';

  /**
   * Instances of this class are created to keep track of conceptual
   * channels created by clients sending long-polling HTTP requests to the
   * service. Each channel object persists over the lifetime of several
   * requests issued using the same channel token.
   *
   * Valid channel lifetime options are `timeout` (channel lifetime limit, in
   * seconds) and `retryLimit` (request attach limit).
   *
   * @param {string}   token     Channel token identifier.
   * @param {Function} onDestroy Channel destruction callback.
   * @param {Object}   [options] Channel lifetime options.
   */
  function Channel(token, onDestroy, options) {
    var self = this;

    /** @private */
    this.token = token;

    /** @private */
    this.onDestroy = onDestroy;

    /** @private */
    this.timeout = (onDestroy !== undefined) ? setTimeout(function () {
      self.destroy();
    }, ((options || {}).timeout || 600) * 1000) : undefined;

    /** @private */
    this.retryLimit = ((options || {}).retryLimit || 10);

    /** @private */
    this.request = undefined;
  }

  /**
   * Attaches given ChannelRequest to channel.
   *
   * If the channel exceeds its retry limit, the channel is immediately
   * destroyed and the Request responded to with a `reconnect` message.
   *
   * @param {ChannelRequest} request Channel request.
   */
  Channel.prototype.attach = function (request) {
    this.request = request;

    if (this.retryLimit <= 0) {
      this.destroy();

    } else {
      this.retryLimit -= 1;
    }
  };

  /**
   * Destroys channel object.
   *
   * There is usually no reason to explicitly destroy a channel, as it will be
   * destroyed automaically when it expires.
   */
  Channel.prototype.destroy = function () {
    if (this.timeout) {
      clearTimeout(this.timeout);
      delete this.timeout;
    }
    if (this.onDestroy) {
      this.onDestroy(this.token);
      delete this.onDestroy;
    }
    if (this.request) {
      this.request.send('reconnect');
      delete this.request;
    }
  };

  /**
   * Notifies channel of received event.
   *
   * @param {string} event Event type, such as "new_event".
   */
  Channel.prototype.notify = function (event) {
    if (this.request) {
      this.request.send(event);
      delete this.request;
    }
  };

  /**
   * Notifies channel of the highest available event ID.
   *
   * May cause an attached ChannelRequest to be responded to and detached.
   *
   * @param {number} id Latest event ID.
   */
  Channel.prototype.notifyOfLatestEventId = function (id) {
    if (this.request && this.request.queryParameters().cursor_position < id) {
      this.request.send('new_event');
      delete this.request;
    }
  };

  /**
   * Sends error message to client, if available, and destroys channel.
   *
   * @param {string} message Description of error.
   */
  Channel.prototype.panic = function (message) {
    if (this.request) {
      this.request.sendError(message);
      delete this.request;
    }
    this.destroy();
  };

  module.exports = Channel;
}());

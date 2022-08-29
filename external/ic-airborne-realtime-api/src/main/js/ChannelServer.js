var iclog = require('./log/iclog');

(function () {
  'use strict';

  var
    ChannelRequest = require('./ChannelRequest'),
    dispatch = require('./http/dispatch'),
    http = require('http');

  /**
   * Wraps suitable HTTP connections into ChannelRequest objects, which are
   * then delegated via a given callback to some other instance.
   *
   * See the internals of the #listen() function for information about
   * available HTTP endpoints.
   *
   * The only valid channel server option is `pingInterval` (delay, in
   * seconds, between each ping sent to available client).
   *
   * @param {Object} delegate  ChannelRequest delegation callback.
   * @param {Object} [options] ChannelServer options.
   */
  function ChannelServer(delegate, options) {
    /** @private */
    this.delegate = delegate;

    /** @private */
    this.options = options;
  }

  /**
   * Starts to listen for incoming HTTP connections on given port.
   *
   * @param  {number} port Port number.
   */
  ChannelServer.prototype.listen = function (port) {
    var self = this;
    http.createServer(
      dispatch({
        'GET /channels/:token': function () {
          self.handleGetChannel.apply(self, arguments);
        },
        'GET /ping': function () {
          self.handlePing.apply(self, arguments);
        },
        '/(.*)': function () {
          self.handleNotFound.apply(self, arguments);
        }
      })
    ).listen(port);
  };

  /** @private */
  ChannelServer.prototype.handleGetChannel = function (req, res, token) {
    var creq, cleanup;

    creq = new ChannelRequest(token, this.parseUrlQuery(req.url), {
      ping: function () {
        res.write(' ');
      },
      send: function (data) {
        res.end(JSON.stringify(data));
      },
      options: this.options
    });

    cleanup = function () {
      creq.destroy();
    };

    res
      .on('close', cleanup)
      .on('finish', cleanup)
      .on('error', function(err) {
        console.log(err);
        iclog.error(err);
        cleanup();
      });

    res.writeHead(200, {
      'Content-Type': 'application/json'
    });

    this.delegate(creq);
  };

  /** @private */
  ChannelServer.prototype.parseUrlQuery = function (url) {
    var
      map = {},
      query = url.split('?')[1];

    if (query) {
      query.split('&').forEach(function (chunk) {
        var pair = chunk.split('=');
        this[pair[0]] = pair[1];
      }, map);
    }
    return map;
  };

  /** @private */
  ChannelServer.prototype.handlePing = function (req, res) {
    res.writeHead(200, {
      'Content-Type': 'text/plain'
    });
    res.end('');
  };

  /** @private */
  ChannelServer.prototype.handleNotFound = function (req, res) {
    res.writeHead(404, {
      'Content-Type': 'text/plain'
    });
    res.end('No resource available at: ' + req.url);
  };

  module.exports = ChannelServer;
}());

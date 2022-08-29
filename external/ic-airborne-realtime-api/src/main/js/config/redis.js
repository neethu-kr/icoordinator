(function () {
  'use strict';

  /**
   * Redis configuration.
   *
   * The configuraiton is altered by providing some or all of the environment
   * variables `MQ_HOST`, and `MQ_PORT`.
   *
   * @module config/redis
   */
  module.exports = {
    /**
     * Verifies that all required Redis configuration fields are set.
     *
     * @return {boolean} True, if and only if, all required fields are set.
     */
    isConfigValid: function () {
      return this.url && true;
    },

    url: process.env.REDIS_URL || 'redis://localhost:6379/0'
  };
}());

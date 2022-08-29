'use strict';

var redis = require('../../../main/js/config/redis');

exports.redis = {
  'Should consider Redis configuration invalid.': function (test) {
    redis.url = undefined;
    test.ok(!redis.isConfigValid());
    test.done();
  },

  'Should consider Redis configuration valid.': function (test) {
    redis.url = 'redis://localhost:6379/1';
    test.ok(redis.isConfigValid());
    test.done();
  }
};

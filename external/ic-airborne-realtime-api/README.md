# iCoordinator Real-Time API

[![Build Status](https://magnum.travis-ci.com/designtech/ic-airborne-realtime-api.svg?token=sRG3rc3fHDzxiRodMkyC)](https://magnum.travis-ci.com/designtech/icoordinator-android)

The iCoordinator Real-Time API service notifies clients of the availability of
server events in real-time. This is facilitated via client long-polling,
meaning that clients connect to the service using long-lived HTTP requests.

This document outlines how to build, test and run the service. Documentation on
how clients may take advantage of the service may be read in the iCoordinator
[API Documentation](https://github.com/designtech/icoordinator-new-api/wiki).

## Building

The Node Package Manager (NPM) is used as dependency manager, while Grunt is
used for building and testing. Grunt, however, is downloaded and managed
transparently by NPM, meaning that it does not need be explicitly installed.

### System Requirements

- Git 2.3.2+
- NodeJS v0.12.7+

### Downloading and Assembling

Download repository into current directory:
```sh
$ git clone git@github.com:designtech/ic-airborne-realtime-api.git
```

Enter created repository folder:
```sh
$ cd ic-airborne-realtime-api
```

Install dependencies and build:
```sh
$ npm install
```

Build:
```sh
$ npm run-script build
```

## Testing

Make sure you are inside the repository root directory.

Run:
```sh
$ npm test
```

## Running

The service relies on being able to connect to and use a Redis service. When
starting the real-time service, appropriate configuration has to be provided
via environment variables. The below table shows what variables are honored by
the service.

| Variable                   | Default                 | Description          |
|:---------------------------|:------------------------|:---------------------|
| REDIS_URL                  | redis://localhost:6379/0| Redis URL.           |
| RTAPI_CHANNEL_TIMEOUT**    | 600                     | Channel timeout (s). |
| RTAPI_CHANNEL_RETRY_LIMIT**| 10                      | Channel reuse limit. |
| RTAPI_REQUEST_PING_INTERVAL| 25                      | Idle req. ping freq. |

** The word __channel__ refers to a client session that persist over multiple
HTTP requests. Such channels are __not__ directly related to Redis channels.

The above table was assembled by looking inside the
[config/redis.js](/src/main/js/config/redis.js) and
[config/config.js](/src/main/js/config/config.js) files.

### Running a Local Server

Make sure you are inside the repository root directory. A local Redis service
is assumed to be present. The RT API service will run on port 8081.

Run:
```sh
$ PORT=8081 node src/main/js/main.js
```
Dummy change to upgrade stack

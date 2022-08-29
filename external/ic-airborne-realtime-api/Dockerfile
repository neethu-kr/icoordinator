FROM node:4-slim

MAINTAINER Emanuel Palm <emanuel.palm@designtech.se>

WORKDIR /data/service/ic-airborne-realtime-api

ADD . /data/service/ic-airborne-realtime-api

RUN npm install --production

EXPOSE 8080

CMD node src/main/js/main.js

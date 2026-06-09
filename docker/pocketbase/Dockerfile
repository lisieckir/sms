FROM alpine:3.21

RUN apk add --no-cache ca-certificates curl jq

ARG PB_VERSION=0.27.0

RUN curl -fsSL https://github.com/pocketbase/pocketbase/releases/download/v${PB_VERSION}/pocketbase_${PB_VERSION}_linux_amd64.zip \
    -o /tmp/pb.zip \
    && unzip /tmp/pb.zip -d /usr/local/bin/ \
    && rm /tmp/pb.zip \
    && chmod +x /usr/local/bin/pocketbase

COPY init.sh /init.sh
RUN chmod +x /init.sh

EXPOSE 8090

CMD ["/bin/sh", "-c", "/init.sh & pocketbase serve --http=0.0.0.0:8090 --dir=/pb_data"]

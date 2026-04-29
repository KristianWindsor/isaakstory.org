FROM php:8.3-cli-alpine

WORKDIR /app

COPY img/ img/
COPY story/ story/
COPY website/ website/

CMD ["php", "-S", "0.0.0.0:8000", "-t", "website", "website/index.php"]

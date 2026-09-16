FROM node:24-alpine

ENV NG_CLI_ANALYTICS=false

RUN npm install -g pnpm@12

WORKDIR /app

CMD ["pnpm", "exec", "ng", "serve", "--host", "0.0.0.0"]

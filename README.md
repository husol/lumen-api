#Introduction
This is CNV API version 2 which is implemented in Lumen framework 5.5. Note that all below config information are fake.

#Configuration features
Please make reference to `.env.example`, copy this file to `.env`, then you should edit with your correct values.

##Database
We use MySQL database management system. Please do the configuration with your database information:

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=cnv-db
DB_USERNAME=root
DB_PASSWORD=secret
```

##Cache
The project is using `Redis` cache which help to improve the performance in some case such as caching data or store data for a queue.
Therefore, you must setup a `Redis` caching server service and update these rows:
```
CACHE_DRIVER=redis
QUEUE_DRIVER=redis

REDIS_HOST=127.0.0.1
REDIS_PORT=6379
```

##JWT
We implement JWT (JSON Web Token) in our API system. Of course we need to setup the configuration of expired time and secret string for this. Below said that the token will be expired after 1 week.
```
JWT_TTL=10080
JWT_SECRET=a5ckuectwfXkP0rAkxKks6mHwPrt29wwCz
```

##S3
We scale the Canavi system. This should be easy to manage and improve the performance of system by using S3 for managing photos data.
To correct your S3 configuration value at:
```
S3_ENDPOINT=https://s3-ap-southeast-1.amazonaws.com
S3_KEY=AAKIAJZOWGTTA7FRNZYGAZ
S3_SECRET=aI2vmshXwsFUSsSyd3g8+3l6RXcNPbG3J0onSWAvrz
S3_BUCKETDAT=cnv-assets-local
```

##Facebook
The important working flow is Facebook connection because the information of jobs or candidates are almost from Facebook. That's the reason why we need to setup a Facebook application and update the config with correct values.
```
FACEBOOK_APP_ID=1829951620624680
FACEBOOK_APP_SECRET=abaed82c69677200dcadb2467535bdcf7z
```

##Firebase
With the high tech, we use Firebase as assistant to trigger on Mobile app through events on real-time database function. You must do the correct configuration on these rows:
```
FIREBASE_CONFIG_URL=https://s3-ap-southeast-1.amazonaws.com/cnv-assets-local/config/cnv-local-firebase-adminsdk.json
FIREBASE_CREDENTIALS=/home/khoaht/cnv/cnv-local-firebase-adminsdk.json
```
Note: If there's no file at `FIREBASE_CREDENTIALS`, the system will download the file from `FIREBASE_CONFIG_URL` and store at `FIREBASE_CREDENTIALS` path.

##OneSignal
The CNV can push notifications to devices if necessary. So we use OneSignal as assistant for this.
```
ONESIGNAL_APP_ID=ad1acd1e2-d48b-4af1-b04a-dcabdce8b718z
ONESIGNAL_API_KEY=aMmFmNTU2MTQtZGY3MC00YjE0LWIwMTAtMjI4MjhjYWIxN2Ixz
```

##Napas
Canavi can do the payment automatically via Napas system which is a famous national payment service in Vietnam.
Below is the example of configuration that we need to have from Napas service. 
```
NAPAS_GATEWAY_URL=https://sandbox.napas.com.vn/gateway/vpcpay.do
NAPAS_MERCHANT_ID=SMLTEST
NAPAS_VERSION=2.0
NAPAS_ACCESS_CODE=ECAFAB
NAPAS_SECRET_KEY=198BE3F2E8C75A53F38C1C4A5B6DBA27
```

##Extra internal config
To set `APP_ENV` to `local`, `beta` or `production` which help our code run well corresponding to this config.

Besides, you can display error log directly with `APP_DEBUG` is `true`.

The `APP_KEY` helps to increase security of system and unique. It's should be set `APP_TIMEZONE` for running well.
```
APP_ENV=local
APP_DEBUG=true
APP_KEY=htkxm{6KFYykGhVD)q~FN+d@}aF%3RbR
APP_TIMEZONE=Asia/Ho_Chi_Minh
```

Sometimes, we need to insert an url of our website to some api response, so we cannot miss:
```
WEB_ROOTURL=http://cnv.net
API_ROOTURL=http://api.cnv.net/v2
```

Any problem?
---
Please send your questions to `khoa@husol.org`, we are ready to support you as soon as possible.

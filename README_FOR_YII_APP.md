# yii2-valet-driver
Yii2 Valet Driver

> install

```
cd ~/.config/valet/Drivers/Specific/
wget https://raw.githubusercontent.com/chinaphp/yii2-valet-driver/master/Yii2ValetDriver.php -O Yii2ValetDriver.php
```

## yii2-app-basic

```
valet link app-name
http://app-name.test
```

## yii2-app-advanced

```
cd backend
valet link backend-app-name
http://backend-app-name.test

cd frontend
valet link frontend-app-name
http://frontend-app-name.test
```
if you want to use aliases domains list

```
cd assets
valet link assets.example
http://assets.example.test/no_image.png
```

require assets subdomain

## yii2-app-advanced-with-single-domain



```
valet link app-name
http://app-name.test/api/

```


<?php
Configure::write('Security.salt', 'QeVELF28iV4KDKhb3Tg4kff6bXdl8ckPh67SCZRK');
Configure::write('Security.cipherSeed', '45980188797908916935051241889');
Configure::write('Cache.disable', false);
Configure::write('Cache.check', true);
Configure::write('Session.save', 'session');
Configure::write('BcEnv.siteUrl', 'http://localhost/ogicomi/');
Configure::write('BcEnv.sslUrl', '');
Configure::write('BcApp.adminSsl', false);
Configure::write('BcApp.mobile', true);
Configure::write('BcApp.smartphone', true);
Cache::config('default', array('engine' => 'File'));
Configure::write('debug', 0);

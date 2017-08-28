<?php
/**
 * Created by PhpStorm.
 * User: Marico
 * Date: 16/6/24
 * Time: 10:50
 */
class Encrypt
{
    // 加密秘钥字符串
    protected static $key = 'Marico_key';
    // 字符替换规则
    protected static $rule = [
        '+' => '*',
        '/' => ':',
        '=' => '_',
    ];

    /**
     * 用户密码加密规则
     * @param $str
     * @return mixed
     */
    public static function md5Pwd($str='')
    {
        return md5(sha1($str).self::$key);
    }

    /**
     *
     * @param $str 需要替换的字符串
     * @param bool|true $is_encrypt 是否为加密
     * @return $str 替换结果字符串
     */
    public static function des_replace($str, $is_encrypt=true)
    {

        // 若为加密,不交换替换键值;若为解密,交换替换键值
        $rule = $is_encrypt ? self::$rule : array_flip(self::$rule);

        foreach($rule as $k => $v)
        {
            $str = str_replace($k,$v,$str);
        }

        return $str;
    }

    /**
     * 数据加密
     * @param $input 输入的值,待加密内容
     * @return string 加密后的内容
     */
    public static function des($input, $key='')
    {
        $key = empty($key)?self::$key:$key;
        $size = mcrypt_get_block_size(MCRYPT_3DES, 'ecb');
        $input = self::des_pkcs5_pad($input, $size);
        $key = str_pad($key,24,'0');
        $td = mcrypt_module_open(MCRYPT_3DES, '', 'ecb', '');
        $iv = @mcrypt_create_iv (mcrypt_enc_get_iv_size($td), MCRYPT_RAND);
        @mcrypt_generic_init($td, $key, $iv);
        $data = mcrypt_generic($td, $input);
        mcrypt_generic_deinit($td);
        mcrypt_module_close($td);
        $data = base64_encode($data);
        return self::des_replace($data);
    }

    /**
     * 数据解密
     * @param $encrypted 加密后的字符串
     * @return bool|string
     */
    public static function undes($encrypted, $key='')
    {
        $key = empty($key)?self::$key:$key;
        $encrypted = self::des_replace($encrypted, false);
        $encrypted = base64_decode($encrypted);
        $key = str_pad($key,24,'0');
        $td = mcrypt_module_open(MCRYPT_3DES,'','ecb','');
        $iv = @mcrypt_create_iv(mcrypt_enc_get_iv_size($td),MCRYPT_RAND);
        //$ks = mcrypt_enc_get_key_size($td);
        @mcrypt_generic_init($td, $key, $iv);
        $decrypted = mdecrypt_generic($td, $encrypted);
        mcrypt_generic_deinit($td);
        mcrypt_module_close($td);
        return self::des_pkcs5_unpad($decrypted);
    }

    /**
     * pkcs5加密
     * @param $text
     * @param $blocksize
     * @return string
     */
    public static function des_pkcs5_pad ($text, $blocksize)
    {
        $pad = $blocksize - (strlen($text) % $blocksize);

        return $text . str_repeat(chr($pad), $pad);
    }

    /**
     * pkcs5解密
     * @param $text
     * @return bool|string
     */
    public static function des_pkcs5_unpad($text)
    {
        $pad = ord($text{strlen($text)-1});

        if ($pad > strlen($text))
        {
            return false;
        }
        if (strspn($text, chr($pad), strlen($text) - $pad) != $pad)
        {
            return false;
        }
        return substr($text, 0, -1 * $pad);
    }

    /**
     * 压缩处理(非绝对加密)
     * @param string $str
     * @param none
     * @return none
     */
    public static function zip_str($str='')
    {
        is_string($str) || $str = serialize($str);
        $data = base64_encode(gzcompress($str, 9));
        return self::des_replace($data);
    }

    /**
     * 解压缩处理(非绝对加密)
     * @param string $str
     * @param none
     * @return none
     */
    public static function unzip_str($str='')
    {
        $str = self::des_replace($str, false);
        return unserialize(gzuncompress(base64_decode($str)));
    }

    /**
     * 随机生成验证码
     * @param none
     * @return string $key 验证码
     */
    public static function randomCode($length=6)
    {
        $key = '';
        $str = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        for($i = 0; $i < $length; $i++)
        {
            $key .= $str[mt_rand(0,62)];
        }
        return $key;
    }
}
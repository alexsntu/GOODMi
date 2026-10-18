// Свои правила ClamAV для веб-шеллов PHP (2026-10-18, после инцидента: официальные базы ClamAV и maldet не узнали
// ни одну из 9 найденных закладок). Признаки – по найденным (Godzilla, Behinder, eval(hex2bin(POST)), загрузчик fileToUpload,
// base32 + eval) и общие для веб-шеллов: функцию или код присылают в запросе и выполняют.

rule GM_Webshell_Godzilla
{
    strings:
        $a = "getBasicsInfo"
        $b = "payloadName"
    condition:
        $a and $b
}

rule GM_Webshell_Behinder
{
    strings:
        $a = "openssl_decrypt"
        $b = "AES128"
        $c = "session_start"
        $d = "eval("
    condition:
        all of them
}

rule GM_Webshell_EvalCommentConcat
{
    strings:
        $a = /eval\s*\(\s*["']\/\*/
    condition:
        $a
}

rule GM_Webshell_EvalHexPost
{
    strings:
        $a = "filter_input(INPUT_POST"
        $b = "hex2bin("
        $c = "eval("
    condition:
        all of them
}

rule GM_Webshell_EvalRequest
{
    strings:
        $a = /(eval|assert)\s*\(\s*(@?(hex2bin|base64_decode|gzinflate|gzuncompress|str_rot13|urldecode|stripslashes)\s*\(\s*)?\$_(POST|GET|REQUEST|COOKIE)/ nocase
    condition:
        $a
}

rule GM_Webshell_RequestAsFunction
{
    strings:
        $a = /\$_(POST|GET|REQUEST|COOKIE)\[[^\]]{1,40}\]\s*\(/
    condition:
        $a
}

rule GM_Webshell_Uploader
{
    strings:
        $a = /move_uploaded_file\s*\(\s*\$_FILES\[[^\]]{1,40}\]\[["']tmp_name["']\]\s*,\s*basename\s*\(\s*\$_FILES/
        $b = "fileToUpload"
    condition:
        $a or $b
}

rule GM_Webshell_Base32Eval
{
    strings:
        $a = "<<= 5"
        $b = "@eval($this->"
    condition:
        $a and $b
}

rule GM_Webshell_EvalDecodedVar
{
    strings:
        $a = /@eval\s*\(\s*(base64_decode|gzinflate|gzuncompress|str_rot13|hex2bin)\s*\(/ nocase
    condition:
        $a
}

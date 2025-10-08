<?php

namespace App\Console\Commands\Resale;

use App\Services\AuthClient;
use App\Services\DavinciClient;
use App\Services\FifaCookies;
use App\Services\ResaleClient;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Cache\Repository as CacheRepository;
use Illuminate\Config\Repository as ConfigRepository;
use Illuminate\Console\Command;

class ReauthenticateCommand extends Command
{
    protected $name = 'resale:reauthenticate';

    private ResaleClient $resaleClient;
    private DavinciClient $davinciClient;
    private AuthClient $authClient;

    public function handle(
        ResaleClient $resaleClient,
        DavinciClient $davinciClient,
        AuthClient $authClient,
        CacheRepository $cache,
        ConfigRepository $config,
        FifaCookies $cookies,

    )
    {
        $this->resaleClient = $resaleClient;
        $this->davinciClient = $davinciClient;
        $this->authClient = $authClient;

        try {
            $response = $this->resaleClient->get('selection/event/date/product/10229225515651/contact-advantages/10229516236677,10229516236679/lang/en');
        } catch (RequestException $e) {
            $response = $e->getResponse();
        }

        $content = $response->getBody()->getContents();

        file_put_contents(storage_path('debug/html/initial_request.html'), $content);

        if (!preg_match('/var skProps = {(.*)}/', $content, $matches)) {
            $this->error('Could not detect skProps');

            return;
        }

        $json     = json_decode('{' . $matches[1] . '}', true);
        $policyId = $json['policyId'];

        $response = $this->davinciClient->post('policy/' . $policyId . '/start', [
            'headers' => [
                'Content-Type'  => 'application/json',
                'Authorization' => 'Bearer ' . $json['accessToken']
            ]
        ]);

        $content = $response->getBody()->getContents();
        file_put_contents(storage_path('debug/html/fifa-policy.html'), $content);

        $json = json_decode($content, true);

        $response = $this->authClient->post('wypKXiRUU3EcXt7aN1V7gUEt/DEiVzN5aQiwNbD/dSdYAlNnRw/TTklEHc/rSFQ', [
            'json' => [
                'sensor_data' => '3;0;1;0;3749190;i+hjvb8mJEkagBSJe5iJSbQVVFl7/h71qvRPLLRbpl4=;31,16,0,2,8,24;"~h"3+p";[4&"%"!k$BlKztoUVFI$-2Ex1Q|Q<4^bjd]i@uL~EY|tuU,uu("}"kfs"w"bX^)D(gZC?# VK"/"k=h"lun_T~M""zZy"v4q"aH!6|"J-m+"c""[">5R"Q""G"N7@"?"I+;0+{0d`1Yo~O11t2C`h=1VMJ~YpsS5|iod!z%vS.h{*nARQcyDGs+>BDyg)2eDB2?Q"S"8,p"1"}"Rj~"Hj1"=60~P"tY9"[gq31N>(tN"c^M_"NY5"jmQ"/pY)@jY_"j]5"u"G3vF."?T}e}"fCN"-A)"q""Z"Sk!"}+70rLLn[Cj)XyK N"ALS"%Fe/?AC3sQX#R57%ss%@)X-OBmM+BA }zD+.g&,78B*T;$|s<BIt>R;`T1hHTg`W`ntqD,=H[2XXUoOoCntz;aS)rAik_w=cY(wuroU+D(ArZRebE&VGz5sNI.?hJnEOb7p*([mW!!9l2TSmb/NM8TpJD0Z5W(6b]8%6m:8KKZ0r1fdV7SEyZcVpm,.56yvGGdQ^Y!W2oRMvCpIC0v7RA}%NUMJb2rU5;zp5V}PFfxV6;DZ(moDk]}74y!EYh:Hj0tX0*yCf,%Qj*Z{GB@nJj[_K$T{v/!$e:93g#nZ[O*^J,p@+nq6bRVu%?DUIAee/a[Z;#N&${q{K)#Uxx(gWorz%EOgiz7W>d>^?S:{$njCA3x,}5"6"J>j"ORr|731J78"yG?"cDK6:x@nHXi2#{"}wQ"VYvG_b""Yww"I[T"(cn"/w="^19{A=EVyl0[[HwwiH!&$z.;"N6="$DNuttK""T"z3g"J"o5G8q0~">"SaU"u"{?1"*"N$^|"-QakbPp|$S"ft4F"D"Q"}M_"2Gc"([+k7mp{!f"DP"p"-8j.QPrLPnNG1fZGZUwf)d#I=Gl?9*JX"1"M(-"h"fDO@tJ)Y<j_{cl".3r/Y"v"boS"<"B:X",*%JnGPy"sKC"Z"n2y|Gt4:G1SawM7p/mrhwrYA~3#ZH{(/Gw|&-[qL&>gFRHz$u_oRv")"C<)"/"*7@8n.2^&n3}B0M{-F-s+AvD:x&oqbIz`y3|k*+_)s9-[LmXdp[W{)j@J,4#*kpF0=dUJmqQ%sZ>qq"m",mq"}`3B$28{xn"0N&m"N]Fch-"PN/e"w"vi4pU[N~%9#{l!W5.1atd6W%LZVxrJ>MR=3+I$Piw_ 2Fv5Z}XzE*SjB;>FG0]1(0k&$;FOetfebIQml7Z5^]JTf,(*q]Q,3CsNH=U]oR (7C<<x,2k75"xxy"YLK"{"GuSP"Xr+"WT/"O*Rss"{xb":O9J0"`7".qLbOb%@"$!_","L_vd::/MDq"lpe"$,T"B"*Tb7`PDol+v(%7xe"#" Bb":3ncj"}S!"u4T!S"4@S?" fz"^Y&~".A1@y"/?"i""W"8a6"^r*ck3"7,AE"="t9N^"8"r/,N">Y6r="7Qm"1"("j$~"T~C"ENL"J~ "w"k;+[A{Ie33=kC@4QiBR5"p"{+w"daa.U1eW(wQ8Q#m$Z"}$Q"e"T*Qb"."QSYg"hH}uQRxp}"SW"EHDl|"|T"/;rz}L??"z:%";""?<Y"-O!"K" "d?$"#Lu"[;3%S~"?sqZSKG"q}E+u9%n)"1uU")Ur}z_(,u="y#_l"&"/Se4Ug,3,pyn{=pF/t?3(l^1/N;/>&~_O72-zjW::.HiFrrut`_&qn!jAixJTH)7"o>&"8US"A"tb"{n$"=<c9"@;9KSeg~V=o9")`%f"w|A"zlh"l"s:uh[kc1"0AK"(J#"d6^K85(usf"7Q3"p"qtr*.?Wr&0)3l9ON0cd8DFSGJ4)b7PR0o}t!1Xq9C`c]B~8G}PO=JJdXo"]"Slz"qkq9Iy/O[,+/sXw|j3"#=3"|"{~VC]ydRZZ~<v(ZmCIJ@-IlU4H"L"GOi"8"b~I>"0"9eH"cwA?iQZ""k:g"tJ"}"8=*|6egQlmNyU"As8"N=E"a"6"Akm"Jcg"8"G{no2E|<$T2[7"m"ErL"Zpk?E"*TS"5""&"[y"F"M"h<g"H>6"{"}]a:g"^>D"@Wy","M"A m"-[<"T"T/nxo6C~9,"[!<"@oZ"."M5vdvJ~b3ceqP5?O 2] -]HH`(hs)[S@Q_y7_u7NLK/m"*HB"7l5"wJnHFS<v"<o8"G3"x?H"x"UhQcuP#<,utLwkJ`K0xBU+y5M[nFKtuX@0>SjgZC|l_"|c_"lS("V&zF}}"jjP.Tut"{"mYM$"i""W"ygs"/rH(134h"g"3"p(sI"L "NQ-"@>e"A""{{W"Aau"EG%b7d+,DmQ"s_#"t"]7_xT".3m"o7=Cn"z"B"o#G"H01"^""kr2"TWL"="P(HGkm?Y!IYXbW^P.8MhD78U+,DG.]w<x$rm@kUHR($"E7Q">B-"r"i`:s8G9tno7<z%^n3 1nc#0uUCC[Prn${!X;ahgEYV%5o}Wp8:T9$9TD-9+HEcq,)zuTs:*p#*Y O{Ii<I];@ZwmNYa}*W`x{{kevLC?J[*J(V.R08]J@d91v~$IW8?Xbtod*[KTfuOxY:o7z$D=5g6:">"Em-">xE",d-"O"A#mJ8`ix/mxS6O"0"C*Lz"p"@/"a" 4M"{Z$iL"Z~r"#H-,84c)"{/"LnWn;"rvPr"a"]|->><8Z5t yka5`I6y/)/>R/? )OrIF.U`N$%;vjFSe*Q,)>}T|xEcsc=+5`Pi>"v:N'
            ]
        ]);
        $content  = $response->getBody()->getContents();
        file_put_contents(storage_path('debug/html/fifa-sensor1.html'), $content);

        $json = $this->davinciRequest($json, [
            'buttonType'  => 'form-submit',
            'buttonValue' => 'login',
            'email'       => $config->get('fifa.auth.username'),
            'password'    => $config->get('fifa.auth.password')
        ]);

        $json = $this->davinciRequest($json, times: 3);

        $otp = $this->ask('OTP?');

        $json = $this->davinciRequest($json, [
            'buttonType'  => 'form-submit',
            'buttonValue' => 'OTP_entered',
            'otp'         => $otp
        ]);

        $response = $this->resaleClient->get('selection/event/date/product/10229225515651/contact-advantages/10229516236677,10229516236679/lang/en');

        $content = $response->getBody()->getContents();
        file_put_contents(storage_path('debug/html/matches.html'), $content);

        $cookies->save($cache);
    }

    private function davinciRequest(
        array $data,
        array $parameters = null,
        int $times = 1
    ): array
    {
        if (null === $parameters) {
            $parameters = [
                'buttonType'  => 'form-submit',
                'buttonValue' => 'frmAutoSubmit',
            ];
        }

        for ($i = 1; $i <= $times; $i++) {
            $response = $this->davinciClient->post('connections/' . $data['connectionId'] . '/capabilities/customHTMLTemplate', [
                'json'    => [
                    'eventName'  => 'continue',
                    'id'         => $data['id'],
                    'nextEvent'  => [
                        'constructType' => 'skEvent',
                        'eventName'     => 'continue',
                        'eventType'     => 'post',
                        'params'        => [],
                        'postProcess'   => (object) []
                    ],
                    'parameters' => $parameters
                ],
                'headers' => [
                    'Interactionid'    => $data['interactionId'],
                    'Interactiontoken' => $data['interactionToken'],
                ]
            ]);

            $content = $response->getBody()->getContents();
            file_put_contents(storage_path('debug/requests/davinci_' . $data['id'] . '_time.json'), $content);

            $data = json_decode($content, true);
        }

        return $data;
    }
}

<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0" 
    xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
    xmlns:xs="http://www.w3.org/2001/XMLSchema">
    
<xsl:output version='1.0' encoding='UTF-8'/>
<xsl:param name="schema-urzedow" select="'KodyUrzedowSkarbowych_v8-0E.xsd'"/>
    
<xsl:template match="/">

<html lang="pl">
<head>
    <title>Urzędowe Potwierdzenie Odbioru</title>
    <meta charset="utf-8"/>
    <style>
        .upo {font-family: 'Arial', sans-serif; max-width: 650px; padding-top: 20px; margin-left: auto; margin-right: auto; font-size: 11pt;}
        .tyt {margin-bottom: 10px; text-align: center; font-size: 14pt;}
        .sek {background-color: #e0e0e0; border: 1px solid black; text-align: left; margin-bottom: 5px; padding-left: 10px}
        .hd  {margin-bottom: 0px;  border-bottom: 0px;}
        .seh {font-weight: normal; margin: 10px 0;}
        .inf {font-size: 10pt; padding-bottom: 10px;}
        .pol {border-top: 1px solid black; border-left: 1px solid black; background-color: white;}
        .p50 {display: table-cell; width: 320px;}
        .ety {text-align: left; font-size: 10pt; padding: 8px; }
        .war {text-align: center; font-size: 12px; padding-top: 15px; padding-bottom: 10px;font-weight: normal;}
        .brl {border-left: 1px solid black;}
        .nip {padding-right: 50px;}
        .stc {padding-bottom: 30px;}
        .stp {padding-top: 10px;}
        .wyd {text-align: left; font-size: 10pt;}
        .wer {float: right;}
        .we2 {font-size: 11pt; border: 1px solid black; display: table-cell; padding: 2px; padding-top: 6px;width: 100px; text-align: center;}
        .nbr {border-right: none;}
        .b {font-weight: bold;}
        .sm {font-size: 10px;}
    </style>
</head>

<body>
    <div class="upo">
         <table style="width:100%; border-collapse: collapse;">
             <tr>
                 <td style="width:120px; vertical-align: middle;">
                     <img height="60" alt="logo" style="margin-bottom:8px" src="data:image/jpeg;base64,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"  />
                 </td>
                 <td style="text-align: center; vertical-align: middle;">
                     <div class="tyt" style="margin-top:10px" >
                         URZĘDOWE POŚWIADCZENIE ODBIORU<br/>
                         DOKUMENTU ELEKTRONICZNEGO
                     </div>
                 </td>
                 <td style="width:120px;">
               
                 </td>
             </tr>
         </table>   
        <xsl:apply-templates select="//Potwierdzenie"/> 
    </div>
</body>

</html>

</xsl:template>

<xsl:template match="Potwierdzenie">

    <div class="sek hd">
        <div class="seh">A. PEŁNA NAZWA PODMIOTU, KTÓREMU DORĘCZONO DOKUMENT ELEKTRONICZNY</div>
        <div class="pol">
            <div class="war"><xsl:value-of select="NazwaPodmiotuPrzyjmujacego"/></div>
        </div>
    </div>

    <div class="sek">
        <div class="seh">B. INFORMACJA O DOKUMENCIE</div>
        
        <div class="inf">Dokument został zarejestrowany w systemie teleinformatycznym Ministerstwa Finansów</div>
        
        <div class="pol">
            <div class="p50">
                <div class="ety">Identyfikator dokumentu:</div>
                <div class="war"><xsl:value-of select="NumerReferencyjny"/></div>
            </div>
            <div class="p50 brl">
                <div class="ety">Dnia (data, czas):</div>
                <div class="war"><xsl:value-of select="DataWplyniecia"/></div>                
            </div>
        </div>
                    
        <div class="pol">
            <div class="ety">Skrót złożonego dokumentu - identyczny z wartością użytą do podpisu dokumentu:</div>
            <div class="war"><xsl:value-of select="SkrotDokumentu"/></div>
        </div>
        
        <div class="pol">
            <div class="ety">Skrót dokumentu w postaci otrzymanej przez system (łącznie z podpisem elektronicznym):</div>
            <div class="war"><xsl:value-of select="SkrotZlozonejStruktury"/></div>
        </div>      
        
        <div class="pol">
            <div class="ety">Dokument zweryfikowano pod względem zgodności ze strukturą logiczną:</div>
            <div class="war"><xsl:value-of select="NazwaStrukturyLogicznej"/></div>
        </div>  
        
        <div class="pol">
            <div class="p50">
                <div class="ety">Identyfikator podatkowy podmiotu występującego jako pierwszy na dokumencie:</div>
                <div class="war"><span class="nip">NIP</span><xsl:value-of select="NIP1"/></div>
            </div>
            <div class="p50 brl">
                <div class="ety">Identyfikator podatkowy podmiotu występującego jako drugi na dokumencie:</div>
                <div class="war"></div>             
            </div>
        </div>
        
        <div class="pol">
            <div class="ety">Urząd skarbowy, do którego został złożony dokument:</div>
            <div class="war">
                <xsl:variable name="schema" select="document($schema-urzedow)"/>
                <xsl:variable name="kodUrzedu" select="KodUrzedu"/>
                <xsl:value-of select="$schema//xs:simpleType[@name='TKodUS']//xs:enumeration[@value=$kodUrzedu]//xs:documentation"/>
            </div>
        </div>  

        <div class="pol">
            <div class="p50">
                <div class="ety">Cel złożenia:</div>
                <div class="war">
                    <xsl:choose> <xsl:when test="CelZlozenia='1'"> złożenie po raz pierwszy dokumentu za dany okres </xsl:when> <xsl:when test="CelZlozenia='2'"> korekta dokumentu </xsl:when> <xsl:otherwise> nieznany cel złożenia </xsl:otherwise> </xsl:choose>
                </div>
            </div>
            <div class="p50 brl">
                <div class="ety">Identyfikacja części pliku i okresu:</div>
                <div class="war">Część deklaracyjna: <xsl:value-of select="format-number(MiesiacCD,'00')"/>/<xsl:value-of select="RokCD"/><br/>Część ewidencyjna: <xsl:value-of select="format-number(MiesiacCE,'00')"/>/<xsl:value-of select="RokCE"/></div>           
            </div>
        </div>

        <div class="pol">
            <div class="ety">Stempel czasu:</div>
            <div class="war stc"><xsl:value-of select="StempelCzasu"/></div>
        </div>          
        
        <div class="pol">
            <div class="ety">Dokument wystawiony automatycznie przez system teleinformatyczny Ministerstwa Finansów:</div>
            <div class="p50">
                <div class="ety">Data i czas wystawienia dokumentu:</div>
            </div>
            <div class="p50">
                <div class="war"><xsl:copy-of select="//*[local-name()='SigningTime']"/></div>
            </div>
        </div>
    </div>
            
    <div class="stp">
        <div class="wyd"></div>
        <div class="wer">
            <div class="we2 nbr">UPO <span class="sm">(<xsl:value-of select="number(substring-before(@wersjaSchemy, '.'))"/>)</span></div>
            <div class="we2">1<span class="sm">/1</span></div>
        </div>
    </div>
    
</xsl:template>

</xsl:stylesheet>
<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:upo="http://upo.schematy.mf.gov.pl/KSeF/v4-3" xmlns:xs="http://www.w3.org/2001/XMLSchema" version="1.0">
    <xsl:output version="1.0" encoding="UTF-8"/>
    <xsl:template match="/">
        <html lang="pl">
            <head>
                <title>Urzędowe Potwierdzenie Odbioru</title>
                <meta charset="utf-8"/>
                <style>
                    .upo {
                        font-family: 'Arial', sans-serif;
                        max-width: 1200px;
                        padding: 20px; 
                        margin: 20px auto; 
                        font-size: 10pt;
                        background-color: #F2F2F2; 
                        border: 1px solid black; 
                        box-sizing: border-box; 
                    }
                    .tyt {
                        margin-bottom: 20px;
                        text-align: center;
                        width: 100%;
                        font-size: 13pt;
                        display: inline-block;
                    }
                    .sek {
                        background-color: #E7E7E7;
                        border: 1px solid black;
                        text-align: left;
                        margin-bottom: -2px
                    }
                    .seh {
                        border: 0px;
                        padding:5px
                    }
                    .inf {
                        font-size: 10pt;
                        padding-bottom: 10px;
                        padding:5px
                    }
                    .pol {
                        border-top: 1px solid black;
                        background-color: white;
                        padding: 5px;
                    }
                    .pol1 {
  
                        background-color: #F2F2F2;
                        padding: 5px;
                    }
                    .pol2 {
                        border-top: 1px solid black;
                        border-bottom: 1px solid black;
                        background-color: white;
                        padding: 5px;
                    }
                    .p50 {
                        display: table-cell;
                        width: 600px;
                    }
                    .ety {
                        text-align: left;
                        font-size: 10pt;
                        padding: 3px;
                        color: black
                    }
                    .war {
                        text-align: center;
                        font-size: 16px;
                        padding-top: 15px;
                        padding-bottom: 10px;
                        word-wrap: break-word;
                        white-space: normal;
                    }
                    .brl {
                        border-left: 1px solid black;
                    }
                    .nip {
                        padding-right: 50px;
                    }
                    .stc {
                        padding-bottom: 30px;
                    }
                    .wyd {
                        text-align: left;
                        font-size: 10pt;
                    }
                    .wer {
                        float: right;
                    }
                    .we2 {
                        font-size: 11pt;
                        border: 1px solid black;
                        display: table-cell;
                        padding: 2px;
                        width: 100px;
                        text-align: center;
                    }
                    .nbr {
                        border-right: none;
                    }
                    .b {
                        font-weight: bold;
                    }
                    table {
                        border-collapse: collapse;
                        width: 100%; /* Ensure the table takes the full width */
                        table-layout: fixed; /* Ensure the columns have fixed widths */
                        border: none;
                    }
                    th {
                        font-size: 14px;
                        background-color: #E7E7E7;
                    }
                    td {
                        font-size: 13pt; /* wielkość czcionki pól tabeli */
                        padding: 4px;
                    }
                    th, td {
                        border: 1px solid black;
                        word-wrap: break-word; /* Ensure the text wraps within the cell */
                        white-space: normal; /* Ensure the text wraps within the cell */
                    }
                    th.column1 { width: 3%; }
                    th.column2 { width: 10%; }  
                    th.column3 { width: 20%; }
                    th.column4 { width: 10%; }
                    th.column5 { width: 20%; }
                    th.column6 { width: 20%; }
                    th.column7 { width: 17%; }
                    .poz {
                        text-align:center;
                        padding: 5px;
                        border-bottom: 0px;
                    }
                </style>
            </head>
            <body>
                <div class="upo">
                    <div>
                        <div class="tyt" style="display: inline-block;width: 100%;">
                            Krajowy System
                            <span style="font-weight:bold;font-size:22px;">
                                <span style="color:red">e</span>-Faktur</span>
                        </div>
                        <div class="tyt">
                            URZĘDOWE POŚWIADCZENIE ODBIORU DOKUMENTU
                            <br/>
                            ELEKTRONICZNEGO KSeF
                        </div>
                    </div>
                    <xsl:apply-templates select="upo:Potwierdzenie"/>
                </div>
            </body>
        </html>
    </xsl:template>
    <xsl:template match="upo:Potwierdzenie">
        <div class="sek">
            <div class="pol1">A. PEŁNA NAZWA PODMIOTU, KTÓREMU DORĘCZONO DOKUMENT ELEKTRONICZNY</div>
            <div class="pol">
                <div class="war">
                    <xsl:value-of select="upo:NazwaPodmiotuPrzyjmujacego"/>
                </div>
            </div>
        </div>
        <div class="sek">
            <div class="pol1">B. INFORMACJA O DOKUMENCIE</div>
            <div class="pol1">Dokument został zarejestrowany w systemie teleinformatycznym Ministerstwa Finansów</div>
    
            <div class="pol">
                <div class="ety">Numer referencyjny sesji:</div>
                <div class="war">
                    <xsl:value-of select="upo:NumerReferencyjnySesji"/>
                </div>
            </div>
            <div class="pol">
                <div class="ety">Identyfikator podatkowy podmiotu:</div>
                <div class="war b">
                    <xsl:value-of select="upo:Uwierzytelnienie/upo:IdKontekstu/upo:Nip"/>
                </div>
            </div>
        
            <div class="pol">
                <div class="ety">Skrót dokumentu uwierzytelniającego:</div>
                <div class="war">
                    <xsl:value-of select="upo:Uwierzytelnienie/upo:SkrotDokumentuUwierzytelniajacego"/>
                </div>
            </div>
            <div class="pol">
                <div class="ety">Nazwa pliku XSD struktury logicznej dotycząca przesłanego dokumentu:</div>
                <div class="war">
                    <xsl:value-of select="upo:NazwaStrukturyLogicznej"/>
                </div>
            </div>
            <div class="pol">
                <div class="ety">Kod formularza przedłożonego dokumentu elektronicznego:</div>
                <div class="war">
                    <xsl:value-of select="upo:KodFormularza"/>
                </div>
            </div>
            <xsl:for-each select="upo:Dokument">
            <div class="pol poz">L.p. <xsl:number/></div>
            <div class="pol">
                <div class="ety">Numer identyfikujący fakturę w KSeF: <xsl:value-of select="upo:NumerKSeFDokumentu"/></div>
                <div class="ety">Numer faktury: <xsl:value-of select="upo:NumerFaktury"/></div>
                <div class="ety">Nip Sprzedawcy: <xsl:value-of select="upo:NipSprzedawcy"/></div>
                <div class="ety">Data wystawienia faktury: <xsl:value-of select="upo:DataWystawieniaFaktury"/></div>
                <div class="ety">Data przesłania do KSeF: <xsl:value-of select="upo:DataPrzeslaniaDokumentu"/></div>
                <div class="ety">Data nadania numeru KSeF: <xsl:value-of select="upo:DataNadaniaNumeruKSeF"/></div>
                <div class="ety">Wartość funkcji skrótu złożonego dokumentu: <xsl:value-of select="upo:SkrotDokumentu"/></div>
            </div>
            </xsl:for-each>

        </div>
    </xsl:template>
</xsl:stylesheet>

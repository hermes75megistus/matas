// MATAS - Maaş Takip Sistemi Yardımcı Fonksiyonlar

/**
 * Taban aylığı hesaplar
 * @param {number} gostergePuani Gösterge puanı
 * @param {number} aylikKatsayi Aylık katsayı
 * @returns {number} Taban aylığı
 */
function hesaplaTabanAyligi(gostergePuani, aylikKatsayi) {
    return Math.round(gostergePuani * aylikKatsayi);
}

/**
 * Ek gösterge aylığı hesaplar
 * @param {number} ekGosterge Ek gösterge puanı
 * @param {number} aylikKatsayi Aylık katsayı
 * @returns {number} Ek gösterge aylığı
 */
function hesaplaEkGostergeAyligi(ekGosterge, aylikKatsayi) {
    return Math.round(ekGosterge * aylikKatsayi);
}

/**
 * Kıdem aylığı hesaplar
 * @param {number} hizmetYili Hizmet yılı
 * @param {number} aylikKatsayi Aylık katsayı
 * @returns {number} Kıdem aylığı
 */
function hesaplaKidemAyligi(hizmetYili, aylikKatsayi) {
    return Math.round(Math.min(hizmetYili, 25) * 25 * aylikKatsayi);
}

/**
 * Yan ödeme hesaplar
 * @param {number} yanOdemePuani Yan ödeme puanı
 * @param {number} yanOdemeKatsayi Yan ödeme katsayısı
 * @returns {number} Yan ödeme tutarı
 */
function hesaplaYanOdeme(yanOdemePuani, yanOdemeKatsayi) {
    return Math.round(yanOdemePuani * yanOdemeKatsayi);
}

/**
 * Özel hizmet tazminatı hesaplar
 * @param {number} gostergePuani Gösterge puanı
 * @param {number} ekGosterge Ek gösterge puanı
 * @param {number} aylikKatsayi Aylık katsayı
 * @param {number} ozelHizmetYuzdesi Özel hizmet yüzdesi
 * @returns {number} Özel hizmet tazminatı
 */
function hesaplaOzelHizmetTazminati(gostergePuani, ekGosterge, aylikKatsayi, ozelHizmetYuzdesi) {
    return Math.round((gostergePuani + ekGosterge) * aylikKatsayi * ozelHizmetYuzdesi / 100);
}

/**
 * İş güçlüğü zammı hesaplar
 * @param {number} isGuclugu İş güçlüğü puanı
 * @param {number} yanOdemeKatsayi Yan ödeme katsayısı
 * @returns {number} İş güçlüğü zammı
 */
function hesaplaIsGucluguzammi(isGuclugu, yanOdemeKatsayi) {
    return Math.round(isGuclugu * yanOdemeKatsayi);
}

/**
 * Dil tazminatı hesaplar
 * @param {string} dilSeviyesi Dil seviyesi (a, b, c)
 * @param {string} dilKullanimi Dil kullanım durumu (evet, hayir)
 * @param {number} aylikKatsayi Aylık katsayı
 * @returns {number} Dil tazminatı
 */
function hesaplaDilTazminati(dilSeviyesi, dilKullanimi, aylikKatsayi) {
    if (dilSeviyesi === 'yok') return 0;
    
    let dilGosterge = 0;
    
    if (dilSeviyesi === 'a' && dilKullanimi === 'evet') {
        dilGosterge = MATAS_CONSTANTS.DIL_GOSTERGELERI.A_KULLANIYOR;
    } else if (dilSeviyesi === 'b' && dilKullanimi === 'evet') {
        dilGosterge = MATAS_CONSTANTS.DIL_GOSTERGELERI.B_KULLANIYOR;
    } else if (dilSeviyesi === 'c' && dilKullanimi === 'evet') {
        dilGosterge = MATAS_CONSTANTS.DIL_GOSTERGELERI.C_KULLANIYOR;
    } else if (dilSeviyesi === 'a' && dilKullanimi === 'hayir') {
        dilGosterge = MATAS_CONSTANTS.DIL_GOSTERGELERI.A_KULLANMIYOR;
    } else if (dilSeviyesi === 'b' && dilKullanimi === 'hayir') {
        dilGosterge = MATAS_CONSTANTS.DIL_GOSTERGELERI.B_KULLANMIYOR;
    } else if (dilSeviyesi === 'c' && dilKullanimi === 'hayir') {
        dilGosterge = MATAS_CONSTANTS.DIL_GOSTERGELERI.C_KULLANMIYOR;
    }
    
    return Math.round(dilGosterge * aylikKatsayi);
}

/**
 * Ek ödeme (666 KHK) hesaplar
 * @param {number} tabanAyligi Taban aylığı
 * @param {number} ekGostergeTutari Ek gösterge tutarı
 * @returns {number} Ek ödeme tutarı
 */
function hesaplaEkOdeme(tabanAyligi, ekGostergeTutari) {
    return Math.round((tabanAyligi + ekGostergeTutari) * 0.20);
}

/**
 * Eğitim-Öğretim tazminatı hesaplar
 * @param {boolean} gorevTazminati Eğitim-öğretim tazminatı alıp almadığı
 * @param {number} tabanAyligi Taban aylığı
 * @param {number} ekGostergeTutari Ek gösterge tutarı
 * @param {number} egitimTazminatOrani Eğitim tazminatı oranı
 * @returns {number} Eğitim-öğretim tazminatı
 */
function hesaplaEgitimTazminati(gorevTazminati, tabanAyligi, ekGostergeTutari, egitimTazminatOrani) {
    if (!gorevTazminati) return 0;
    return Math.round((tabanAyligi + ekGostergeTutari) * egitimTazminatOrani);
}

/**
 * Geliştirme ödeneği hesaplar
 * @param {boolean} gelistirmeOdenegi Geliştirme ödeneği alıp almadığı
 * @param {number} tabanAyligi Taban aylığı
 * @param {number} ekGostergeTutari Ek gösterge tutarı
 * @param {number} gelistirmeOrani Geliştirme oranı
 * @returns {number} Geliştirme ödeneği tutarı
 */
function hesaplaGelistirmeOdenegi(gelistirmeOdenegi, tabanAyligi, ekGostergeTutari, gelistirmeOrani) {
    if (!gelistirmeOdenegi) return 0;
    return Math.round((tabanAyligi + ekGostergeTutari) * gelistirmeOrani);
}

/**
 * Makam tazminatı hesaplar
 * @param {number} makamTazminatPuani Makam tazminatı puanı
 * @param {number} aylikKatsayi Aylık katsayı
 * @returns {number} Makam tazminatı
 */
function hesaplaMakamTazminati(makamTazminatPuani, aylikKatsayi) {
    if (!makamTazminatPuani) return 0;
    return Math.round(makamTazminatPuani * aylikKatsayi / 10);
}

/**
 * Lisansüstü tazminatı hesaplar
 * @param {string} egitimDurumu Eğitim durumu (lisans, yuksek_lisans, doktora)
 * @param {number} tabanAyligi Taban aylığı
 * @returns {number} Lisansüstü eğitim tazminatı
 */
function hesaplaLisansustuTazminat(egitimDurumu, tabanAyligi) {
    if (egitimDurumu === 'yuksek_lisans') {
        return Math.round(tabanAyligi * MATAS_CONSTANTS.LISANSUSTU_ORANLAR.YUKSEK_LISANS);
    } else if (egitimDurumu === 'doktora') {
        return Math.round(tabanAyligi * MATAS_CONSTANTS.LISANSUSTU_ORANLAR.DOKTORA);
    }
    return 0;
}

/**
 * Aile yardımı hesaplar
 * @param {string} medeniHal Medeni hal (evli, bekar)
 * @param {string} esCalisiyor Eşin çalışma durumu (evet, hayir)
 * @returns {number} Aile yardımı
 */
function hesaplaAileYardimi(medeniHal, esCalisiyor) {
    if (medeniHal === 'evli' && esCalisiyor === 'hayir') {
        return MATAS_CONSTANTS.SOSYAL_YARDIMLAR.AILE_YARDIMI;
    }
    return 0;
}

/**
 * Çocuk yardımı hesaplar
 * @param {number} cocukSayisi Toplam çocuk sayısı
 * @param {number} cocuk06 0-6 yaş arası çocuk sayısı
 * @param {number} engelliCocuk Engelli çocuk sayısı
 * @param {number} ogrenimCocuk Öğrenim gören çocuk sayısı
 * @returns {number} Çocuk yardımı
 */
function hesaplaCocukYardimi(cocukSayisi, cocuk06, engelliCocuk, ogrenimCocuk) {
    const normalCocuk = cocukSayisi - cocuk06 - engelliCocuk - ogrenimCocuk;
    
    const yardim06Yas = cocuk06 * MATAS_CONSTANTS.SOSYAL_YARDIMLAR.COCUK_0_6;
    const yardimNormal = normalCocuk * MATAS_CONSTANTS.SOSYAL_YARDIMLAR.COCUK_NORMAL;
    const yardimEngelli = engelliCocuk * MATAS_CONSTANTS.SOSYAL_YARDIMLAR.COCUK_ENGELLI;
    const yardimOgrenim = ogrenimCocuk * MATAS_CONSTANTS.SOSYAL_YARDIMLAR.COCUK_OGRENIM;
    
    return yardim06Yas + yardimNormal + yardimEngelli + yardimOgrenim;
}

/**
 * Kira yardımı hesaplar
 * @param {boolean} kiraYardimi Kira yardımı alıp almadığı
 * @returns {number} Kira yardımı tutarı
 */
function hesaplaKiraYardimi(kiraYardimi) {
    return kiraYardimi ? MATAS_CONSTANTS.SOSYAL_YARDIMLAR.KIRA_YARDIMI : 0;
}

/**
 * Sendika yardımı hesaplar
 * @param {boolean} sendikaUyesi Sendika üyesi olup olmadığı
 * @returns {number} Sendika yardımı tutarı
 */
function hesaplaSendikaYardimi(sendikaUyesi) {
    return sendikaUyesi ? MATAS_CONSTANTS.SOSYAL_YARDIMLAR.SENDIKA_YARDIMI : 0;
}

/**
 * Emekli keseneği hesaplar
 * @param {number} tabanAyligi Taban aylığı
 * @param {number} ekGostergeTutari Ek gösterge tutarı
 * @param {number} kidemAyligi Kıdem aylığı
 * @returns {number} Emekli keseneği
 */
function hesaplaEmekliKesenegi(tabanAyligi, ekGostergeTutari, kidemAyligi) {
    const emekliMatrahi = tabanAyligi + ekGostergeTutari + kidemAyligi;
    return Math.round(emekliMatrahi * MATAS_CONSTANTS.EMEKLI_KESENEK_ORANI);
}

/**
 * Genel sağlık sigortası hesaplar
 * @param {number} tabanAyligi Taban aylığı
 * @param {number} ekGostergeTutari Ek gösterge tutarı
 * @param {number} kidemAyligi Kıdem aylığı
 * @returns {number} Genel sağlık sigortası
 */
function hesaplaGSS(tabanAyligi, ekGostergeTutari, kidemAyligi) {
    const emekliMatrahi = tabanAyligi + ekGostergeTutari + kidemAyligi;
    return Math.round(emekliMatrahi * MATAS_CONSTANTS.GSS_ORANI);
}

/**
 * Gelir vergisi hesaplar
 * @param {number} matrah Gelir vergisi matrahı
 * @param {number} kumulatifVergi Kümülatif vergi
 * @returns {number} Gelir vergisi
 */
function hesaplaGelirVergisi(matrah, kumulatifVergi) {
    let vergi = 0;
    let kalanMatrah = matrah;
    
    for (let i = 0; i < MATAS_CONSTANTS.VERGI_DILIMLERI.length; i++) {
        const dilim = MATAS_CONSTANTS.VERGI_DILIMLERI[i];
        const oncekiDilimLimit = i === 0 ? 0 : MATAS_CONSTANTS.VERGI_DILIMLERI[i - 1].LIMIT;
        
        if (kalanMatrah <= 0) break;
        
        if (i === MATAS_CONSTANTS.VERGI_DILIMLERI.length - 1 || dilim.LIMIT === Infinity) {
            // Son dilim
            vergi += kalanMatrah * dilim.ORAN;
            kalanMatrah = 0;
        } else {
            // Ara dilimler
            const dilimFarki = dilim.LIMIT - oncekiDilimLimit;
            const hesaplanacak = Math.min(kalanMatrah, dilimFarki);
            vergi += hesaplanacak * dilim.ORAN;
            kalanMatrah -= hesaplanacak;
        }
    }
    
    return Math.round(vergi - kumulatifVergi);
}

/**
 * Damga vergisi hesaplar
 * @param {number} brutMaas Brüt maaş
 * @param {number} istisna İstisna tutarı
 * @returns {number} Damga vergisi
 */
function hesaplaDamgaVergisi(brutMaas, istisna) {
    return Math.round((brutMaas - istisna) * MATAS_CONSTANTS.DAMGA_VERGISI_ORANI);
}

/**
 * Sendika kesintisi hesaplar
 * @param {boolean} sendikaUyesi Sendika üyesi olup olmadığı
 * @param {number} tabanAyligi Taban aylığı
 * @returns {number} Sendika kesintisi
 */
function hesaplaSendikaKesintisi(sendikaUyesi, tabanAyligi) {
    return sendikaUyesi ? Math.round(tabanAyligi * MATAS_CONSTANTS.SENDIKA_KESINTI_ORANI) : 0;
}

/**
 * Brüt maaşı hesaplar
 * @param {number} tabanAyligi Taban aylığı
 * @param {number} ekGostergeTutari Ek gösterge tutarı
 * @param {number} kidemAyligi Kıdem aylığı
 * @param {number} yanOdeme Yan ödeme
 * @param {number} ozelHizmetTazminati Özel hizmet tazminatı
 * @param {number} isGucluguzammi İş güçlüğü zammı
 * @param {number} dilTazminati Dil tazminatı
 * @param {number} ekOdeme Ek ödeme
 * @param {number} egitimTazminati Eğitim tazminatı
 * @param {number} gelistirmeOdenegiTutari Geliştirme ödeneği
 * @param {number} makamTazminati Makam tazminatı
 * @param {number} lisansustuTazminat Lisansüstü tazminat
 * @param {number} aileYardimi Aile yardımı
 * @param {number} cocukYardimi Çocuk yardımı
 * @param {number} kiraYardimiTutari Kira yardımı
 * @param {number} sendikaYardimi Sendika yardımı
 * @returns {number} Brüt maaş
 */
function hesaplaBrutMaas(tabanAyligi, ekGostergeTutari, kidemAyligi, yanOdeme, ozelHizmetTazminati, 
                         isGucluguzammi, dilTazminati, ekOdeme, egitimTazminati, gelistirmeOdenegiTutari, 
                         makamTazminati, lisansustuTazminat, aileYardimi, cocukYardimi, kiraYardimiTutari, sendikaYardimi) {
    return tabanAyligi + ekGostergeTutari + kidemAyligi + yanOdeme + ozelHizmetTazminati + 
           isGucluguzammi + dilTazminati + ekOdeme + egitimTazminati + gelistirmeOdenegiTutari + 
           makamTazminati + lisansustuTazminat + aileYardimi + cocukYardimi + kiraYardimiTutari + sendikaYardimi;
}

/**
 * Net maaşı hesaplar
 * @param {number} brutMaas Brüt maaş
 * @param {number} toplamKesintiler Toplam kesintiler
 * @returns {number} Net maaş
 */
function hesaplaNetMaas(brutMaas, toplamKesintiler) {
    return brutMaas - toplamKesintiler;
} 

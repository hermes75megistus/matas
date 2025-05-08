// MATAS - Maaş Takip Sistemi Sabit Değerleri
const MATAS_CONSTANTS = {
    // Minimum Memur Aylığı (2025 Ocak-Haziran dönemi için)
    MIN_MEMUR_AYLIGI: 24500,
    
    // Asgari Geçim İndirimi Oranları
    AGI_ORANLAR: {
        BEKAR: 0.50,
        EVLI_ES_CALISMAYAN: 0.60,
        COCUK_1: 0.075,
        COCUK_2: 0.075,
        COCUK_3: 0.10,
        COCUK_4: 0.05,
        COCUK_5_VE_UZERI: 0
    },
    
    // Vergi Matrahı Dilimleri (2025 yılı için)
    VERGI_DILIMLERI: [
        { LIMIT: 70000, ORAN: 0.15 },
        { LIMIT: 150000, ORAN: 0.20 },
        { LIMIT: 550000, ORAN: 0.27 },
        { LIMIT: 1900000, ORAN: 0.35 },
        { LIMIT: Infinity, ORAN: 0.40 }
    ],
    
    // Damga Vergisi Oranı
    DAMGA_VERGISI_ORANI: 0.00759,
    
    // Emekli Keseneği Oranı
    EMEKLI_KESENEK_ORANI: 0.16,
    
    // Genel Sağlık Sigortası Oranı
    GSS_ORANI: 0.05,
    
    // Sendika Kesintisi Oranı
    SENDIKA_KESINTI_ORANI: 0.01,
    
    // Katsayı Sabitleri (2025 Ocak-Haziran dönemi için)
    KATSAYILAR: {
        AYLIK_KATSAYI: 0.354507,
        TABAN_AYLIK_KATSAYI: 7.715,
        YAN_ODEME_KATSAYI: 0.0354507
    },
    
    // Maaş Bileşenleri
    BILESEN_TIPLER: {
        KAZANC: 'kazanc',
        KESINTI: 'kesinti',
        SOSYAL_YARDIM: 'sosyal_yardim'
    },
    
    // Eğitim Öğretim Tazminatı Oranları
    EGITIM_TAZMINAT_ORANLARI: {
        OGRETMEN: 0.20,
        REKTOR: 0.27,
        DEKAN: 0.25,
        MUDUR: 0.22,
        OGRETIM_UYESI: 0.20
    },
    
    // Dil Tazminatı Göstergeleri
    DIL_GOSTERGELERI: {
        A_KULLANIYOR: 1500,
        B_KULLANIYOR: 600,
        C_KULLANIYOR: 300,
        A_KULLANMIYOR: 750,
        B_KULLANMIYOR: 300,
        C_KULLANMIYOR: 150
    },
    
    // Lisansüstü Eğitim Tazminatı Oranları
    LISANSUSTU_ORANLAR: {
        YUKSEK_LISANS: 0.05,
        DOKTORA: 0.15
    },
    
    // Sosyal Yardım Tutarları (2025 için)
    SOSYAL_YARDIMLAR: {
        AILE_YARDIMI: 1200,
        COCUK_NORMAL: 150,
        COCUK_0_6: 300,
        COCUK_ENGELLI: 600,
        COCUK_OGRENIM: 250,
        KIRA_YARDIMI: 2000,
        SENDIKA_YARDIMI: 500
    }
};

// Derece-Kademe Gösterge Puanları
const GOSTERGE_PUANLARI = {
    // 1. Derece
    '1-1': 1320,
    '1-2': 1380,
    '1-3': 1440,
    '1-4': 1500,
    '1-5': 1560,
    '1-6': 1620,
    '1-7': 1680,
    '1-8': 1740,
    
    // 2. Derece
    '2-1': 1155,
    '2-2': 1210,
    '2-3': 1265,
    '2-4': 1320,
    '2-5': 1380,
    '2-6': 1440,
    '2-7': 1500,
    '2-8': 1560,
    
    // 3. Derece
    '3-1': 1020,
    '3-2': 1065,
    '3-3': 1110,
    '3-4': 1155,
    '3-5': 1210,
    '3-6': 1265,
    '3-7': 1320,
    '3-8': 1380,
    '3-9': 1440,
    
    // 4. Derece
    '4-1': 915,
    '4-2': 950,
    '4-3': 985,
    '4-4': 1020,
    '4-5': 1065,
    '4-6': 1110,
    '4-7': 1155,
    '4-8': 1210,
    '4-9': 1265,
    
    // 5. Derece
    '5-1': 835,
    '5-2': 870,
    '5-3': 905,
    '5-4': 915,
    '5-5': 950,
    '5-6': 985,
    '5-7': 1020,
    '5-8': 1065,
    '5-9': 1110,
    
    // 6. Derece
    '6-1': 760,
    '6-2': 785,
    '6-3': 810,
    '6-4': 835,
    '6-5': 870,
    '6-6': 905,
    '6-7': 915,
    '6-8': 950,
    '6-9': 985,
    
    // 7. Derece
    '7-1': 705,
    '7-2': 720,
    '7-3': 740,
    '7-4': 760,
    '7-5': 785,
    '7-6': 810,
    '7-7': 835,
    '7-8': 870,
    '7-9': 905,
    
    // 8. Derece
    '8-1': 660,
    '8-2': 675,
    '8-3': 690,
    '8-4': 705,
    '8-5': 720,
    '8-6': 740,
    '8-7': 760,
    '8-8': 785,
    '8-9': 810,
    
    // 9. Derece
    '9-1': 620,
    '9-2': 630,
    '9-3': 645,
    '9-4': 660,
    '9-5': 675,
    '9-6': 690,
    '9-7': 705,
    '9-8': 720,
    '9-9': 740,
    
    // 10. Derece
    '10-1': 590,
    '10-2': 600,
    '10-3': 610,
    '10-4': 620,
    '10-5': 630,
    '10-6': 645,
    '10-7': 660,
    '10-8': 675,
    '10-9': 690,
    
    // 11. Derece
    '11-1': 560,
    '11-2': 570,
    '11-3': 580,
    '11-4': 590,
    '11-5': 600,
    '11-6': 610,
    '11-7': 620,
    '11-8': 630,
    '11-9': 645,
    
    // 12. Derece
    '12-1': 545,
    '12-2': 550,
    '12-3': 555,
    '12-4': 560,
    '12-5': 570,
    '12-6': 580,
    '12-7': 590,
    '12-8': 600,
    '12-9': 610,
    
    // 13. Derece
    '13-1': 530,
    '13-2': 535,
    '13-3': 540,
    '13-4': 545,
    '13-5': 550,
    '13-6': 555,
    '13-7': 560,
    '13-8': 570,
    '13-9': 580,
    
    // 14. Derece
    '14-1': 515,
    '14-2': 520,
    '14-3': 525,
    '14-4': 530,
    '14-5': 535,
    '14-6': 540,
    '14-7': 545,
    '14-8': 550,
    '14-9': 555,
    
    // 15. Derece
    '15-1': 500,
    '15-2': 505,
    '15-3': 510,
    '15-4': 515,
    '15-5': 520,
    '15-6': 525,
    '15-7': 530,
    '15-8': 535,
    '15-9': 540
}; 

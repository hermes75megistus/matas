=== MATAS - Maaş Takip Sistemi ===
Contributors: yourname
Tags: maas, takip, memur, memur maaşı, maaş hesaplama
Requires at least: 5.0
Tested up to: 6.4
Requires PHP: 7.0
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Türkiye'deki memurların maaşlarını hesaplayan modern ve kullanıcı dostu bir WordPress eklentisi.

== Description ==

MATAS - Maaş Takip Sistemi, Türkiye'deki memurların maaşlarını hesaplamak için geliştirilmiş kapsamlı bir WordPress eklentisidir. Kullanıcı dostu, modern arayüzü ve detaylı hesaplama özellikleriyle memur maaşlarını kolayca hesaplayabilirsiniz.

= Özellikler =

* Modern ve kullanıcı dostu arayüz
* Detaylı maaş bileşenleri hesaplama
* Tüm unvan ve kadro derecelerine göre hesaplama
* Ek gösterge, yan ödeme ve özel hizmet tazminatlarını hesaplama
* Aile yardımı, çocuk yardımı gibi sosyal yardımları dahil etme
* Vergi kesintileri (gelir vergisi ve damga vergisi) hesaplama
* Emekli keseneği ve sağlık sigortası hesaplama
* Sonuçları kaydetme ve geri yükleme özelliği
* Güncel katsayılarla hesaplama (2025 Ocak-Haziran dönemi)
* Yönetici panelinden katsayı ve değerleri güncelleme

= Nasıl Kullanılır =

Eklentiyi kurduktan sonra herhangi bir sayfa veya yazıya `[matas_hesaplama]` kısa kodunu ekleyin. Bu kısa kod maaş hesaplama formunu sayfanıza yerleştirir.

Kısa kodun parametreleri:

* `baslik`: Hesaplama formunun başlığını değiştirir. Örnek: `[matas_hesaplama baslik="2025 Memur Maaş Hesaplama"]`
* `stil`: Farklı görünümler için stil parametresi. Örnek: `[matas_hesaplama stil="modern"]`

== Installation ==

1. Eklenti dosyalarını `/wp-content/plugins/matas-maas-takip/` dizinine yükleyin veya WordPress yönetici panelinden 'Eklentiler > Yeni Ekle' menüsünden eklentiyi arayıp yükleyin.
2. WordPress yönetici panelinden eklentiyi etkinleştirin.
3. Herhangi bir sayfa veya yazıya `[matas_hesaplama]` kısa kodunu ekleyin.

== Frequently Asked Questions ==

= Hesaplamalar ne kadar doğrudur? =

Hesaplamalar gerçek maaş hesaplama formüllerine dayanmaktadır, ancak resmi olmayan sonuçlar içerebilir. Kesin bilgi için kurumunuzun özlük birimine başvurmanızı öneririz.

= Katsayıları güncelleyebilir miyim? =

Evet, WordPress yönetici panelindeki MATAS menüsünden katsayıları güncelleyebilirsiniz.

= Eklenti hangi dönem için hesaplama yapıyor? =

Eklenti varsayılan olarak 2025 Ocak-Haziran dönemi katsayılarıyla hesaplama yapar, ancak yönetici panelinden bu değerleri güncelleyebilirsiniz.

== Screenshots ==

1. Maaş hesaplama formu
2. Hesaplama sonuçları
3. Yönetici paneli - Katsayılar
4. Yönetici paneli - Ünvan Bilgileri
5. Yönetici paneli - Gösterge Puanları

== Changelog ==

= 1.0.0 =
* İlk sürüm

== Upgrade Notice ==

= 1.0.0 =
Bu MATAS - Maaş Takip Sistemi'nin ilk sürümüdür.

== Türkiye'deki Memur Maaşı Hesaplama ==

MATAS eklentisi, Türkiye'deki memurların maaşlarını hesaplamak için aşağıdaki bileşenleri dikkate alır:

= Temel Aylık Bileşenleri =
* Taban Aylık: Derece ve kademeye göre belirlenen gösterge puanı ile aylık katsayının çarpımı.
* Ek Gösterge: Unvana göre belirlenen ek gösterge ile aylık katsayının çarpımı.
* Kıdem Aylığı: Hizmet yılı (en fazla 25 yıl) × 25 × aylık katsayı.
* Yan Ödeme: Unvana göre belirlenen yan ödeme puanı × yan ödeme katsayısı.
* Özel Hizmet Tazminatı: (Gösterge puanı + Ek gösterge) × Aylık katsayı × (Özel hizmet oranı / 100).

= Ek Ödemeler ve Tazminatlar =
* Ek Ödeme (666 KHK): (Taban aylık + Ek gösterge) × 0.20
* Eğitim-Öğretim Tazminatı: Eğitim kurumlarında çalışanlar için.
* Yabancı Dil Tazminatı: Yabancı dil sınavından alınan puana göre.
* Lisansüstü Eğitim Tazminatı: Yüksek lisans için %5, doktora için %15.
* Makam/Görev/Temsil Tazminatı: Belirli üst düzey görevliler için.

= Sosyal Yardımlar =
* Aile Yardımı: Eşin çalışmaması durumunda ödenir.
* Çocuk Yardımı: Her bir çocuk için ödenir, 0-6 yaş, engelli veya öğrenim durumuna göre farklılık gösterir.
* Kira Yardımı: Belirli şartları sağlayanlar için.
* Sendika Yardımı: Sendika üyeleri için.

= Kesintiler =
* Emekli Keseneği: %16
* Genel Sağlık Sigortası: %5
* Gelir Vergisi: Gelir vergisi matrahına göre kademeli olarak hesaplanır.
* Damga Vergisi: Brüt maaş × 0.00759
# Shelly RPC — inwentaryzacja bez sterowania

Stan z 2026-09-27. Odczyty wykonano wyłącznie metodami informacyjnymi (`/shelly`, `Shelly.GetDeviceInfo`, `Shelly.GetStatus`, `Shelly.GetConfig`, `Webhook.List`, `Schedule.List`, `Script.List`). Nie wysyłano `Switch.Set` ani innych poleceń zmieniających stan.

W aplikacji występują 22 unikatowe identyfikatory urządzeń. W sieci `192.168.1.0/24` udało się potwierdzić 12 urządzeń:

| ID (MAC) | IP w chwili odczytu | Sprzęt / profil | Komponenty / rola |
| --- | --- | --- | --- |
| `cc7b5c8378b4` | `192.168.1.16` | Plus 1PM Gen 2 | `switch:0` — pompa kominka |
| `84fce63e8abc` | `192.168.1.12` | PM Mini Gen 3 | `pm1:0` — pomiar TV |
| `2cbcbb2dc408` | `192.168.1.51` | Plus 2PM Gen 2, `cover` | `cover:0` — roleta |
| `9451dc0ac424` | `192.168.1.73` | Plus RGBW PM Gen 2, `light` | `light:0..3` — zawór prawy |
| `fce8c0fd0a7c` | `192.168.1.83` | Plus 1PM Gen 2 | `switch:0` — hydrofor |
| `30c922573230` | `192.168.1.99` | Plus RGBW PM Gen 2, `light` | `light:0..3` — zawór lewy |
| `2cbcbbc16fa4` | `192.168.1.117` | Plus RGBW PM Gen 2, `light` | `light:0..3` — LED kuchnia |
| `345f45193b80` | `192.168.1.118` | Plus 2PM Gen 2, `switch` | `switch:0..1` — halogen/ girlanda |
| `ecc9ff4dc3f4` | `192.168.1.120` | Plus RGBW PM Gen 2, `light` | `light:0..3` — LED TV |
| `ecc9ff4b35e4` | `192.168.1.167` | Plus 2PM Gen 2, `switch` | `switch:0..1` — pompy zasilanie/powrót |
| `b0b21c103c08` | `192.168.1.195` | Plus 1PM Gen 2 | `switch:0` — światła podjazdu |
| `64b708097270` | `192.168.1.239` | Plus 1PM Gen 2 | `switch:0` — pompa CWU |

Nie odnaleziono w tym przebiegu: `1c6920097bbc` (garaż), `3030f9eb9e5c` (licznik solarny), `5432046b0fd8` (licznik kotła), `543204705c18` (licznik kominka), `8cbfea9bedac` (pokój dziewczyn), `9451dc0ab154` (LED sypialnia), `dcda0cb72d50` (sypialnia), `dcda0cb76520` (gabinet), `e4b3233129e0` (pokój chłopca), `fcb4672633b4` (salon). Brak odpowiedzi w jednym skanowaniu **nie dowodzi**, że urządzenia nie obsługują RPC; trzeba sprawdzić ich adresy, dostępność i ewentualną inną podsieć.

## Zależności i ryzyka przed przełączeniem sterowania

- Pompa CWU ma 5 harmonogramów urządzenia, w tym 3 aktywne: włączenie w dni robocze o 06:30, wyłączenie o północy i aktualizacja firmware o 04:00. Dwa pozostałe harmonogramy przełącznika są wyłączone. Nie wyłączać ich bez rozstrzygnięcia, kto ma być jedynym źródłem sterowania.
- Pompy ogrzewania mają łącznie 9 webhooków, hydrofor 1, pompa kominka 5, pompa CWU 1. Część webhooków innych urządzeń prowadzi także do lokalnych adresów; licznik TV wysyła m.in. do `192.168.1.120`, a LED TV ma skrypt. Zachować te zależności podczas migracji.
- W klasach pięciu urządzeń dodano konfigurację RPC. Obecność konfiguracji kieruje **zarówno odczyt, jak i zapis** przełącznika do lokalnego RPC; dla urządzeń bez konfiguracji RPC pozostaje ścieżka Cloud. Wdrożenie tej wersji zmienia więc sposób sterowania pompami kominka, CWU, zasilania i powrotu oraz hydroforem. Nie jest to wyłącznie zmiana diagnostyczna.
- Pięć zaworów na dwóch sterownikach Plus RGBW PM (`30c922573230` i `9451dc0ac424`) korzysta na branchu home-core z `Light.Set`. Otwarcie zawsze podaje `brightness: 100` i dodatni `toggle_after`; przed zapisem aplikacja sprawdza MAC urządzenia, więc przestarzały zapasowy adres IP nie skieruje polecenia do innego sterownika. Wersja hostingowa nadal korzysta z Cloud.
- Pięć białych kanałów LED na sterownikach TV (`ecc9ff4dc3f4`) i kuchni (`2cbcbbc16fa4`) korzysta na branchu home-core z lokalnego `Light.Set`. Zachowują jasności podawane przez automatyzacje i weryfikują MAC przed zapisem. Sceny Shelly Cloud pozostają osobnym etapem i mogą nadal oddziaływać na LED-y TV i kuchni.
- Kolorowa taśma `BedLeds` (`9451dc0ab154`) została rozpoznana przez Cloud jako Shelly Plus RGBW PM Gen 2 z komponentem `rgbw:0`; Cloud zgłasza adres `192.168.1.105`. Na branchu home-core obsługuje ją `RGBW.Set` i `RGBW.GetStatus`, ale bezpośredni odczyt RPC z workstation pod tym IP zakończył się timeoutem. Potwierdzić dostępność z home-core przed przełączeniem automatyzacji; nie było testowego zapisu do taśmy.
- Nie przeprowadzać testów zapisu na pompach, zaworach, roletach ani innych nieuzgodnionych odbiornikach. Testy adapterów zapisu wykonać na atrapach HTTP. Dla pomp najpierw ustalić pojedynczego wykonawcę automatyzacji (hosting albo home-core) oraz zachowanie przy utracie łączności.

## Następny bezpieczny etap

Ponowić tylko odczyt dla 10 nieodnalezionych ID z home-core i ustalić ich typy/komponenty. Następnie przygotować osobne adaptery statusu dla `pm1` i `cover` oraz testy na atrapach odpowiedzi. Przełączanie wykonawcy harmonogramów nawadniania i pozostałych automatyzacji uzgadniać osobno; na danym etapie tylko jedna instancja powinna wykonywać zadania.

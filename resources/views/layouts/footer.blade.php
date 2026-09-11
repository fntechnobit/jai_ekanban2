<!-- Footer Section starts-->
<footer>
    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-9 col-12">
                <ul class="footer-text">
                    <li>
                        <p class="mb-0">Powered by <a href="https://technobit.co.id" target="_blank">Technobit Indonesia</a></p>
                    </li>
                    {{-- Penanda versi kode yang benar-benar berjalan. Sha commit dan waktu
                         berkasnya dibaca dari .git saat request, sehingga langsung terlihat
                         apakah server sudah ikut `git pull` (dan sudah lepas dari OPcache). --}}
                    @php($versiApp = \App\Support\AppVersion::info())
                    <li>
                        <a href="#" onclick="return false;" title="{{ \App\Support\AppVersion::keterangan() }}">
                            V{{ config('app.version', '1.0.0') }}@if ($versiApp['source'] !== 'config')
                                <span class="text-secondary">· {{ $versiApp['short'] }}</span>
                            @endif
                            @if ($versiApp['deployed_at'])
                                <span class="text-secondary d-none d-md-inline">· {{ $versiApp['deployed_at']->format('d M Y H:i') }}</span>
                            @endif
                        </a>
                    </li>
                </ul>
            </div>
            <div class="col-sm-3">
                <ul class="footer-text text-end">
                    <li><a href="mailto:support@technobit.co.id">Need Help <i class="fa-solid fa-circle-question"></i></a></li>
                </ul>
            </div>
        </div>
    </div>
</footer>
<!-- Footer Section ends-->

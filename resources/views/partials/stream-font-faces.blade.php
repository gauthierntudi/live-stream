<style>
    @font-face {
        font-family: 'Maswen';
        src: url('{{ asset('fonts/maswen-2.otf') }}') format('opentype');
        font-weight: 400;
        font-style: normal;
        font-display: swap;
    }
    @font-face {
        font-family: 'Antonio';
        src: url('{{ asset('fonts/Antonio-Bold.ttf') }}') format('truetype');
        font-weight: 700;
        font-style: normal;
        font-display: swap;
    }
    @font-face {
        font-family: 'Montserrat';
        src: url('{{ asset('fonts/Montserrat-VariableFont_wght.ttf') }}') format('truetype');
        font-weight: 100 900;
        font-style: normal;
        font-display: swap;
    }
    @font-face {
        font-family: 'Cooper Hewitt';
        src: url('{{ asset('fonts/CooperHewitt-Semibold_2.otf') }}') format('opentype');
        font-weight: 600;
        font-style: normal;
        font-display: swap;
    }
    @font-face {
        font-family: 'BentonSans';
        src: url('{{ asset('fonts/' . rawurlencode('BentonSans Black.otf')) }}') format('opentype');
        font-weight: 900;
        font-style: normal;
        font-display: swap;
    }
    @font-face {
        font-family: 'Century Gothic';
        src: url('{{ asset('fonts/CenturyGothicPaneuropeanBold.ttf') }}') format('truetype');
        font-weight: 700;
        font-style: normal;
        font-display: swap;
    }
    @font-face {
        font-family: 'Acumin';
        src: url('{{ asset('fonts/AcuminVariableConcept.otf') }}') format('opentype');
        font-weight: 100 900;
        font-style: normal;
        font-display: swap;
    }
    :root {
        --font-stream-body: 'Montserrat', ui-sans-serif, system-ui, sans-serif;
        --font-stream-display: 'Antonio', 'Century Gothic', ui-sans-serif, sans-serif;
        --font-stream-brand: 'Maswen', 'Antonio', ui-sans-serif, sans-serif;
    }
</style>

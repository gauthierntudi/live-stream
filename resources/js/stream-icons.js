import { createIcons } from 'lucide';
import {
    Activity,
    ArrowLeft,
    Banknote,
    Check,
    CirclePlay,
    Clock,
    Coins,
    CreditCard,
    CircleCheck,
    CircleX,
    Filter,
    Globe,
    Hash,
    Heart,
    Home,
    Lock,
    Mail,
    MessageSquare,
    Phone,
    Power,
    Receipt,
    RotateCcw,
    Search,
    ShieldCheck,
    Sparkles,
    TvMinimalPlay,
    User,
    Wallet,
    X,
} from 'lucide';

const streamIcons = {
        Activity,
        ArrowLeft,
        Banknote,
        Check,
        CirclePlay,
        Clock,
        Coins,
        CreditCard,
        CircleCheck,
        CircleX,
        Filter,
        Globe,
        Hash,
        Heart,
        Home,
        Lock,
        Mail,
        MessageSquare,
        Phone,
        Power,
        Receipt,
        RotateCcw,
        Search,
        ShieldCheck,
        Sparkles,
        TvMinimalPlay,
        User,
        Wallet,
        X,
};

createIcons({ icons: streamIcons });

export function refreshStreamIcons() {
    createIcons({ icons: streamIcons });
}

window.refreshStreamIcons = refreshStreamIcons;

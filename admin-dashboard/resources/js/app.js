import './behaviors';
import { createIcons, CalendarCheck, ChevronDown, Clock, House, LayoutDashboard, LogOut, MapPin, Menu, Phone, Plus, Search, Printer, Sparkles, Trash2, Users, Wallet, Contact, CreditCard, FileText, ChartColumn, Building2, UserCog, ShieldCheck, X } from 'lucide';

createIcons({ icons: { CalendarCheck, ChevronDown, Clock, House, LayoutDashboard, LogOut, MapPin, Menu, Phone, Plus, Search, Printer, Sparkles, Trash2, Users, Wallet, Contact, CreditCard, FileText, ChartColumn, Building2, UserCog, ShieldCheck, X } });

document.querySelectorAll('[data-sidebar-toggle]').forEach((button) => {
    button.addEventListener('click', () => document.body.toggleAttribute('data-sidebar-open'));
});

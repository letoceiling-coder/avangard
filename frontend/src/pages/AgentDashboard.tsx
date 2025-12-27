import { useState } from "react";
import { Building2, Users, TrendingUp, Target, Edit, Trash2, Download, Phone, Mail, Calendar, Eye, Plus, FileText, DollarSign } from "lucide-react";
import Header from "@/components/Header";
import Footer from "@/components/Footer";
import { Card, CardContent } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table";
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import { useAuth } from "@/hooks/useAuth";
import { useFavorites } from "@/hooks/useFavorites";
import { useNavigate } from "react-router-dom";

// HTML escape function to prevent XSS
const escapeHtml = (text: string): string => {
  const div = document.createElement('div');
  div.textContent = text;
  return div.innerHTML;
};

// Mock data for agent's objects
const mockObjects = [
  {
    id: "1",
    title: "2-к квартира, 65 м²",
    address: "ЖК Солнечный, ул. Ленина 15",
    status: "active",
    price: 5500000,
    views: 156,
    leads: 3,
    commission: 82500,
  },
  {
    id: "2",
    title: "3-к квартира, 89 м²",
    address: "ЖК Центральный, пр. Мира 42",
    status: "reserved",
    price: 8900000,
    views: 89,
    leads: 1,
    commission: 133500,
  },
  {
    id: "3",
    title: "1-к квартира, 38 м²",
    address: "ЖК Новый, ул. Гагарина 7",
    status: "sold",
    price: 3200000,
    views: 234,
    leads: 5,
    commission: 48000,
  },
  {
    id: "4",
    title: "Студия, 28 м²",
    address: "ЖК Молодежный, ул. Советская 23",
    status: "active",
    price: 2100000,
    views: 67,
    leads: 2,
    commission: 31500,
  },
];

// Mock data for leads
const mockLeads = [
  {
    id: "1",
    name: "Иван Петров",
    phone: "+7 (900) 123-45-67",
    email: "ivan@example.com",
    objectId: "1",
    objectTitle: "2-к квартира, 65 м², ЖК Солнечный",
    status: "new",
    createdAt: "2024-01-15T10:30:00",
    message: "Интересует рассрочка и возможность просмотра в выходные",
  },
  {
    id: "2",
    name: "Мария Сидорова",
    phone: "+7 (900) 987-65-43",
    email: "maria@example.com",
    objectId: "2",
    objectTitle: "3-к квартира, 89 м², ЖК Центральный",
    status: "contacted",
    createdAt: "2024-01-14T15:45:00",
    message: "Хотела бы узнать о планировке и документах",
  },
  {
    id: "3",
    name: "Алексей Козлов",
    phone: "+7 (900) 555-44-33",
    email: "alex@example.com",
    objectId: "1",
    objectTitle: "2-к квартира, 65 м², ЖК Солнечный",
    status: "viewing",
    createdAt: "2024-01-13T09:00:00",
    message: "Готов выйти на сделку при торге",
  },
  {
    id: "4",
    name: "Елена Новикова",
    phone: "+7 (900) 111-22-33",
    email: "elena@example.com",
    objectId: "4",
    objectTitle: "Студия, 28 м², ЖК Молодежный",
    status: "new",
    createdAt: "2024-01-15T14:20:00",
    message: "Рассматриваю для инвестиций",
  },
];

// Mock data for commissions
const mockCommissions = [
  {
    id: "1",
    objectTitle: "3-к квартира, 89 м², ЖК Центральный",
    dealDate: "2024-01-10",
    dealPrice: 8900000,
    commissionRate: 1.5,
    commissionAmount: 133500,
    status: "pending",
    expectedDate: "2024-02-10",
  },
  {
    id: "2",
    objectTitle: "1-к квартира, 38 м², ЖК Новый",
    dealDate: "2024-01-05",
    dealPrice: 3200000,
    commissionRate: 1.5,
    commissionAmount: 48000,
    status: "paid",
    paidDate: "2024-01-20",
  },
  {
    id: "3",
    objectTitle: "2-к квартира, 52 м², ЖК Парковый",
    dealDate: "2023-12-20",
    dealPrice: 4500000,
    commissionRate: 1.5,
    commissionAmount: 67500,
    status: "paid",
    paidDate: "2024-01-10",
  },
];

const AgentDashboard = () => {
  const { user, isAuthenticated } = useAuth();
  const { favorites } = useFavorites();
  const navigate = useNavigate();
  const [activeTab, setActiveTab] = useState("overview");
  const [selectedLead, setSelectedLead] = useState<typeof mockLeads[0] | null>(null);
  const [isLeadModalOpen, setIsLeadModalOpen] = useState(false);

  // Calculate stats
  const objectsCount = mockObjects.length;
  const activeLeads = mockLeads.filter(l => l.status === "new" || l.status === "contacted").length;
  const pendingCommission = mockCommissions
    .filter(c => c.status === "pending")
    .reduce((sum, c) => sum + c.commissionAmount, 0);
  const totalLeads = mockLeads.length;
  const convertedLeads = mockLeads.filter(l => l.status === "viewing").length;
  const conversionRate = totalLeads > 0 ? Math.round((convertedLeads / totalLeads) * 100) : 0;

  const formatPrice = (price: number) => {
    return new Intl.NumberFormat("ru-RU", {
      style: "currency",
      currency: "RUB",
      maximumFractionDigits: 0,
    }).format(price);
  };

  const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString("ru-RU", {
      day: "numeric",
      month: "long",
      year: "numeric",
    });
  };

  const getStatusBadge = (status: string) => {
    const statusConfig: Record<string, { label: string; variant: "default" | "success" | "warning" | "error" | "info" | "outline" }> = {
      active: { label: "Активно", variant: "success" },
      reserved: { label: "Бронь", variant: "warning" },
      sold: { label: "Продано", variant: "outline" },
      new: { label: "Новая", variant: "info" },
      contacted: { label: "В работе", variant: "warning" },
      viewing: { label: "Показ", variant: "success" },
      pending: { label: "Ожидает", variant: "warning" },
      paid: { label: "Выплачено", variant: "success" },
    };
    const config = statusConfig[status] || { label: status, variant: "outline" as const };
    return <Badge variant={config.variant}>{config.label}</Badge>;
  };

  const handleLeadClick = (lead: typeof mockLeads[0]) => {
    setSelectedLead(lead);
    setIsLeadModalOpen(true);
  };

  const handleGeneratePDF = () => {
    // Generate PDF from favorites
    const printWindow = window.open("", "_blank");
    if (!printWindow) return;

    const content = `
      <!DOCTYPE html>
      <html>
        <head>
          <title>Подборка объектов - LiveGrid</title>
          <style>
            body { font-family: Arial, sans-serif; padding: 40px; color: #333; }
            h1 { color: #4AB3FF; border-bottom: 2px solid #4AB3FF; padding-bottom: 10px; }
            .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
            .agent-info { background: #f5f5f5; padding: 15px; border-radius: 8px; margin-bottom: 30px; }
            .property { border: 1px solid #ddd; padding: 20px; margin-bottom: 20px; border-radius: 8px; }
            .property-title { font-size: 18px; font-weight: bold; color: #0A2342; }
            .property-price { font-size: 20px; color: #4AB3FF; font-weight: bold; margin: 10px 0; }
            .property-details { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-top: 15px; }
            .detail { font-size: 14px; color: #666; }
            .footer { margin-top: 40px; text-align: center; color: #999; font-size: 12px; }
          </style>
        </head>
        <body>
          <div class="header">
            <h1>Подборка объектов</h1>
            <p>Дата: ${new Date().toLocaleDateString("ru-RU")}</p>
          </div>
          <div class="agent-info">
            <strong>Агент:</strong> ${escapeHtml(user?.name || "LiveGrid Agent")}<br>
            <strong>Телефон:</strong> ${escapeHtml(user?.phone || "+7 (800) 123-45-67")}<br>
            <strong>Email:</strong> ${escapeHtml(user?.email || "agent@livegrid.ru")}
          </div>
          ${favorites.length > 0 ? favorites.map(prop => `
            <div class="property">
              <div class="property-title">${escapeHtml(prop.title)}</div>
              <div class="property-price">${formatPrice(prop.price)}</div>
              <p>${escapeHtml(prop.address)}</p>
              <div class="property-details">
                <div class="detail"><strong>Площадь:</strong> ${prop.area} м²</div>
                <div class="detail"><strong>Комнат:</strong> ${prop.rooms}</div>
                <div class="detail"><strong>Этаж:</strong> ${prop.floor}</div>
                <div class="detail"><strong>Цена за м²:</strong> ${formatPrice(prop.price / prop.area)}</div>
              </div>
            </div>
          `).join("") : "<p>Добавьте объекты в избранное для создания подборки</p>"}
          <div class="footer">
            Сгенерировано на платформе LiveGrid • livegrid.ru
          </div>
        </body>
      </html>
    `;

    printWindow.document.write(content);
    printWindow.document.close();
    printWindow.print();
  };

  if (!isAuthenticated) {
    return (
      <div className="min-h-screen bg-background">
        <Header />
        <main className="container mx-auto px-4 py-16 text-center">
          <Building2 className="w-16 h-16 text-muted-foreground mx-auto mb-4" />
          <h1 className="text-2xl font-bold text-foreground mb-2">Кабинет агента</h1>
          <p className="text-muted-foreground mb-6">Войдите в систему для доступа к кабинету агента</p>
          <Button onClick={() => navigate("/auth")}>Войти</Button>
        </main>
        <Footer />
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-background">
      <Header />
      
      <main className="container mx-auto px-4 py-8">
        {/* Page Header */}
        <div className="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
          <div>
            <h1 className="text-2xl md:text-3xl font-bold text-foreground">Кабинет агента</h1>
            <p className="text-muted-foreground mt-1">Управление объектами, заявками и комиссиями</p>
          </div>
          <Button className="gap-2" onClick={() => navigate("/catalog")}>
            <Plus className="w-4 h-4" />
            Добавить объект
          </Button>
        </div>

        {/* Stats Cards */}
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
          <Card>
            <CardContent className="p-6">
              <div className="flex items-start justify-between">
                <div>
                  <p className="text-sm text-muted-foreground mb-1">Мои объекты</p>
                  <p className="text-3xl font-bold text-foreground">{objectsCount}</p>
                </div>
                <div className="p-2 bg-primary/10 rounded-lg">
                  <Building2 className="w-6 h-6 text-primary" />
                </div>
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardContent className="p-6">
              <div className="flex items-start justify-between">
                <div>
                  <p className="text-sm text-muted-foreground mb-1">Активные лиды</p>
                  <p className="text-3xl font-bold text-foreground">{activeLeads}</p>
                </div>
                <div className="p-2 bg-primary/10 rounded-lg">
                  <Users className="w-6 h-6 text-primary" />
                </div>
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardContent className="p-6">
              <div className="flex items-start justify-between">
                <div>
                  <p className="text-sm text-muted-foreground mb-1">К получению</p>
                  <p className="text-3xl font-bold text-primary">{formatPrice(pendingCommission)}</p>
                </div>
                <div className="p-2 bg-primary/10 rounded-lg">
                  <TrendingUp className="w-6 h-6 text-primary" />
                </div>
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardContent className="p-6">
              <div className="flex items-start justify-between">
                <div>
                  <p className="text-sm text-muted-foreground mb-1">Конверсия</p>
                  <p className="text-3xl font-bold text-foreground">{conversionRate}%</p>
                </div>
                <div className="p-2 bg-primary/10 rounded-lg">
                  <Target className="w-6 h-6 text-primary" />
                </div>
              </div>
            </CardContent>
          </Card>
        </div>

        {/* Tabs */}
        <Tabs value={activeTab} onValueChange={setActiveTab} className="space-y-6">
          <TabsList className="grid w-full grid-cols-4 lg:w-auto lg:inline-grid">
            <TabsTrigger value="overview">Обзор</TabsTrigger>
            <TabsTrigger value="objects">Объекты</TabsTrigger>
            <TabsTrigger value="leads">Заявки</TabsTrigger>
            <TabsTrigger value="commissions">Комиссии</TabsTrigger>
          </TabsList>

          {/* Overview Tab */}
          <TabsContent value="overview" className="space-y-6">
            {/* PDF Generator Card */}
            <Card>
              <CardContent className="p-6">
                <div className="flex flex-col md:flex-row md:items-center justify-between gap-4">
                  <div>
                    <h2 className="text-xl font-bold text-foreground mb-2">Генератор подборок</h2>
                    <p className="text-muted-foreground">
                      Создайте красивую PDF-подборку для отправки клиентам ({favorites.length} объектов в избранном)
                    </p>
                  </div>
                  <Button onClick={handleGeneratePDF} className="gap-2" disabled={favorites.length === 0}>
                    <Download className="w-5 h-5" />
                    Создать подборку
                  </Button>
                </div>
              </CardContent>
            </Card>

            {/* Recent Leads */}
            <Card>
              <CardContent className="p-6">
                <div className="flex items-center justify-between mb-6">
                  <h2 className="text-xl font-bold text-foreground">Последние заявки</h2>
                  <Button variant="ghost" size="sm" onClick={() => setActiveTab("leads")}>
                    Все заявки
                  </Button>
                </div>

                <div className="space-y-4">
                  {mockLeads.slice(0, 3).map((lead) => (
                    <div
                      key={lead.id}
                      className="p-4 border border-border rounded-lg hover:border-primary transition-colors cursor-pointer"
                      onClick={() => handleLeadClick(lead)}
                    >
                      <div className="flex items-start justify-between mb-2">
                        <div>
                          <h3 className="font-semibold text-foreground">{lead.name}</h3>
                          <p className="text-sm text-muted-foreground">{lead.phone}</p>
                        </div>
                        {getStatusBadge(lead.status)}
                      </div>
                      <p className="text-sm text-muted-foreground">
                        <strong>Объект:</strong> {lead.objectTitle}
                      </p>
                    </div>
                  ))}
                </div>
              </CardContent>
            </Card>

            {/* Quick Stats */}
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              <Card>
                <CardContent className="p-6">
                  <h3 className="text-lg font-semibold text-foreground mb-4">Статистика объектов</h3>
                  <div className="space-y-3">
                    <div className="flex justify-between items-center">
                      <span className="text-muted-foreground">Всего просмотров</span>
                      <span className="font-semibold">{mockObjects.reduce((sum, o) => sum + o.views, 0)}</span>
                    </div>
                    <div className="flex justify-between items-center">
                      <span className="text-muted-foreground">Активных объектов</span>
                      <span className="font-semibold">{mockObjects.filter(o => o.status === "active").length}</span>
                    </div>
                    <div className="flex justify-between items-center">
                      <span className="text-muted-foreground">В брони</span>
                      <span className="font-semibold">{mockObjects.filter(o => o.status === "reserved").length}</span>
                    </div>
                    <div className="flex justify-between items-center">
                      <span className="text-muted-foreground">Продано</span>
                      <span className="font-semibold">{mockObjects.filter(o => o.status === "sold").length}</span>
                    </div>
                  </div>
                </CardContent>
              </Card>

              <Card>
                <CardContent className="p-6">
                  <h3 className="text-lg font-semibold text-foreground mb-4">Финансы</h3>
                  <div className="space-y-3">
                    <div className="flex justify-between items-center">
                      <span className="text-muted-foreground">Ожидает выплаты</span>
                      <span className="font-semibold text-primary">{formatPrice(pendingCommission)}</span>
                    </div>
                    <div className="flex justify-between items-center">
                      <span className="text-muted-foreground">Выплачено за месяц</span>
                      <span className="font-semibold">
                        {formatPrice(mockCommissions.filter(c => c.status === "paid").reduce((sum, c) => sum + c.commissionAmount, 0))}
                      </span>
                    </div>
                    <div className="flex justify-between items-center">
                      <span className="text-muted-foreground">Средняя комиссия</span>
                      <span className="font-semibold">1.5%</span>
                    </div>
                  </div>
                </CardContent>
              </Card>
            </div>
          </TabsContent>

          {/* Objects Tab */}
          <TabsContent value="objects">
            <Card>
              <CardContent className="p-6">
                <div className="flex items-center justify-between mb-6">
                  <h2 className="text-xl font-bold text-foreground">Мои объекты</h2>
                  <Button size="sm" className="gap-2">
                    <Plus className="w-4 h-4" />
                    Добавить
                  </Button>
                </div>

                <div className="overflow-x-auto">
                  <Table>
                    <TableHeader>
                      <TableRow>
                        <TableHead>Объект</TableHead>
                        <TableHead>Статус</TableHead>
                        <TableHead className="text-right">Цена</TableHead>
                        <TableHead className="text-center">Просмотры</TableHead>
                        <TableHead className="text-center">Заявки</TableHead>
                        <TableHead className="text-right">Комиссия</TableHead>
                        <TableHead className="text-center">Действия</TableHead>
                      </TableRow>
                    </TableHeader>
                    <TableBody>
                      {mockObjects.map((obj) => (
                        <TableRow key={obj.id}>
                          <TableCell>
                            <div>
                              <p className="font-semibold text-foreground">{obj.title}</p>
                              <p className="text-xs text-muted-foreground">{obj.address}</p>
                            </div>
                          </TableCell>
                          <TableCell>{getStatusBadge(obj.status)}</TableCell>
                          <TableCell className="text-right font-bold text-primary">
                            {formatPrice(obj.price)}
                          </TableCell>
                          <TableCell className="text-center">
                            <div className="flex items-center justify-center gap-1">
                              <Eye className="w-4 h-4 text-muted-foreground" />
                              {obj.views}
                            </div>
                          </TableCell>
                          <TableCell className="text-center">{obj.leads}</TableCell>
                          <TableCell className="text-right text-muted-foreground">
                            {formatPrice(obj.commission)}
                          </TableCell>
                          <TableCell>
                            <div className="flex gap-2 justify-center">
                              <Button variant="ghost" size="icon" className="h-8 w-8">
                                <Edit className="w-4 h-4" />
                              </Button>
                              <Button variant="ghost" size="icon" className="h-8 w-8 text-destructive hover:text-destructive">
                                <Trash2 className="w-4 h-4" />
                              </Button>
                            </div>
                          </TableCell>
                        </TableRow>
                      ))}
                    </TableBody>
                  </Table>
                </div>
              </CardContent>
            </Card>
          </TabsContent>

          {/* Leads Tab */}
          <TabsContent value="leads">
            <Card>
              <CardContent className="p-6">
                <h2 className="text-xl font-bold text-foreground mb-6">Поступившие заявки</h2>

                <div className="space-y-4">
                  {mockLeads.map((lead) => (
                    <div
                      key={lead.id}
                      className="p-4 border border-border rounded-lg hover:border-primary transition-colors"
                    >
                      <div className="flex items-start justify-between mb-3">
                        <div>
                          <h3 className="font-semibold text-foreground">{lead.name}</h3>
                          <div className="flex flex-wrap gap-3 text-sm text-muted-foreground mt-1">
                            <span className="flex items-center gap-1">
                              <Phone className="w-3 h-3" />
                              {lead.phone}
                            </span>
                            <span className="flex items-center gap-1">
                              <Mail className="w-3 h-3" />
                              {lead.email}
                            </span>
                          </div>
                        </div>
                        {getStatusBadge(lead.status)}
                      </div>
                      
                      <p className="text-sm text-muted-foreground mb-2">
                        <strong>Объект:</strong> {lead.objectTitle}
                      </p>
                      
                      {lead.message && (
                        <p className="text-sm text-foreground bg-muted/50 p-3 rounded-lg mb-3">
                          "{lead.message}"
                        </p>
                      )}
                      
                      <div className="flex items-center justify-between">
                        <p className="text-xs text-muted-foreground flex items-center gap-1">
                          <Calendar className="w-3 h-3" />
                          {formatDate(lead.createdAt)}
                        </p>
                        <div className="flex gap-2">
                          <Button variant="secondary" size="sm">Ответить</Button>
                          <Button variant="ghost" size="sm" onClick={() => handleLeadClick(lead)}>
                            Подробнее
                          </Button>
                        </div>
                      </div>
                    </div>
                  ))}
                </div>
              </CardContent>
            </Card>
          </TabsContent>

          {/* Commissions Tab */}
          <TabsContent value="commissions">
            <Card>
              <CardContent className="p-6">
                <div className="flex items-center justify-between mb-6">
                  <h2 className="text-xl font-bold text-foreground">Отслеживание комиссий</h2>
                  <div className="flex items-center gap-2 text-sm">
                    <DollarSign className="w-4 h-4 text-primary" />
                    <span className="text-muted-foreground">Всего к получению:</span>
                    <span className="font-bold text-primary">{formatPrice(pendingCommission)}</span>
                  </div>
                </div>

                <div className="overflow-x-auto">
                  <Table>
                    <TableHeader>
                      <TableRow>
                        <TableHead>Объект</TableHead>
                        <TableHead>Дата сделки</TableHead>
                        <TableHead className="text-right">Сумма сделки</TableHead>
                        <TableHead className="text-center">Ставка</TableHead>
                        <TableHead className="text-right">Комиссия</TableHead>
                        <TableHead>Статус</TableHead>
                        <TableHead>Дата выплаты</TableHead>
                      </TableRow>
                    </TableHeader>
                    <TableBody>
                      {mockCommissions.map((commission) => (
                        <TableRow key={commission.id}>
                          <TableCell>
                            <p className="font-medium text-foreground">{commission.objectTitle}</p>
                          </TableCell>
                          <TableCell className="text-muted-foreground">
                            {formatDate(commission.dealDate)}
                          </TableCell>
                          <TableCell className="text-right">
                            {formatPrice(commission.dealPrice)}
                          </TableCell>
                          <TableCell className="text-center">
                            {commission.commissionRate}%
                          </TableCell>
                          <TableCell className="text-right font-bold text-primary">
                            {formatPrice(commission.commissionAmount)}
                          </TableCell>
                          <TableCell>{getStatusBadge(commission.status)}</TableCell>
                          <TableCell className="text-muted-foreground">
                            {commission.status === "paid" 
                              ? formatDate(commission.paidDate!) 
                              : commission.expectedDate 
                                ? `Ожидается: ${formatDate(commission.expectedDate)}`
                                : "—"
                            }
                          </TableCell>
                        </TableRow>
                      ))}
                    </TableBody>
                  </Table>
                </div>

                {/* Commission Summary */}
                <div className="mt-6 p-4 bg-muted/50 rounded-lg">
                  <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div className="text-center">
                      <p className="text-sm text-muted-foreground">Всего выплачено</p>
                      <p className="text-xl font-bold text-foreground">
                        {formatPrice(mockCommissions.filter(c => c.status === "paid").reduce((sum, c) => sum + c.commissionAmount, 0))}
                      </p>
                    </div>
                    <div className="text-center">
                      <p className="text-sm text-muted-foreground">Ожидает выплаты</p>
                      <p className="text-xl font-bold text-primary">{formatPrice(pendingCommission)}</p>
                    </div>
                    <div className="text-center">
                      <p className="text-sm text-muted-foreground">Сделок всего</p>
                      <p className="text-xl font-bold text-foreground">{mockCommissions.length}</p>
                    </div>
                  </div>
                </div>
              </CardContent>
            </Card>
          </TabsContent>
        </Tabs>
      </main>

      {/* Lead Detail Modal */}
      <Dialog open={isLeadModalOpen} onOpenChange={setIsLeadModalOpen}>
        <DialogContent className="sm:max-w-md">
          <DialogHeader>
            <DialogTitle>Детали заявки</DialogTitle>
          </DialogHeader>
          {selectedLead && (
            <div className="space-y-4">
              <div>
                <p className="text-sm text-muted-foreground">Клиент</p>
                <p className="font-semibold text-foreground">{selectedLead.name}</p>
              </div>
              <div className="grid grid-cols-2 gap-4">
                <div>
                  <p className="text-sm text-muted-foreground">Телефон</p>
                  <p className="font-medium text-foreground">{selectedLead.phone}</p>
                </div>
                <div>
                  <p className="text-sm text-muted-foreground">Email</p>
                  <p className="font-medium text-foreground">{selectedLead.email}</p>
                </div>
              </div>
              <div>
                <p className="text-sm text-muted-foreground">Объект</p>
                <p className="font-medium text-foreground">{selectedLead.objectTitle}</p>
              </div>
              <div>
                <p className="text-sm text-muted-foreground">Сообщение</p>
                <p className="text-foreground bg-muted/50 p-3 rounded-lg">{selectedLead.message}</p>
              </div>
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-sm text-muted-foreground">Статус</p>
                  {getStatusBadge(selectedLead.status)}
                </div>
                <div>
                  <p className="text-sm text-muted-foreground">Дата</p>
                  <p className="text-sm">{formatDate(selectedLead.createdAt)}</p>
                </div>
              </div>
              <div className="flex gap-2 pt-4">
                <Button className="flex-1 gap-2">
                  <Phone className="w-4 h-4" />
                  Позвонить
                </Button>
                <Button variant="secondary" className="flex-1 gap-2">
                  <Mail className="w-4 h-4" />
                  Написать
                </Button>
              </div>
            </div>
          )}
        </DialogContent>
      </Dialog>

      <Footer />
    </div>
  );
};

export default AgentDashboard;

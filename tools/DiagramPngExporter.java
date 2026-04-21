import javax.imageio.ImageIO;
import java.awt.BasicStroke;
import java.awt.Color;
import java.awt.Font;
import java.awt.FontMetrics;
import java.awt.GradientPaint;
import java.awt.Graphics2D;
import java.awt.Polygon;
import java.awt.RenderingHints;
import java.awt.Stroke;
import java.awt.geom.RoundRectangle2D;
import java.awt.image.BufferedImage;
import java.io.IOException;
import java.nio.file.Files;
import java.nio.file.Path;
import java.nio.file.Paths;
import java.util.ArrayList;
import java.util.List;

public class DiagramPngExporter {
    private static final Color BG = new Color(248, 250, 252);
    private static final Color TITLE = new Color(15, 23, 42);
    private static final Color SUBTITLE = new Color(71, 85, 105);
    private static final Color ENTITY_FILL = new Color(226, 232, 240);
    private static final Color ENTITY_BORDER = new Color(71, 85, 105);
    private static final Color PROCESS_FILL = new Color(219, 234, 254);
    private static final Color PROCESS_BORDER = new Color(37, 99, 235);
    private static final Color STORE_FILL = new Color(220, 252, 231);
    private static final Color STORE_BORDER = new Color(22, 163, 74);
    private static final Color USECASE_FILL = new Color(255, 247, 237);
    private static final Color USECASE_BORDER = new Color(234, 88, 12);
    private static final Color ARROW = new Color(51, 65, 85);
    private static final Color LABEL_BG = new Color(255, 255, 255, 235);
    private static final Color BOUNDARY = new Color(148, 163, 184);

    private static final Font TITLE_FONT = new Font("SansSerif", Font.BOLD, 34);
    private static final Font SUBTITLE_FONT = new Font("SansSerif", Font.PLAIN, 19);
    private static final Font SHAPE_TITLE_FONT = new Font("SansSerif", Font.BOLD, 20);
    private static final Font SHAPE_TEXT_FONT = new Font("SansSerif", Font.PLAIN, 17);
    private static final Font LABEL_FONT = new Font("SansSerif", Font.PLAIN, 15);
    private static final Font SMALL_FONT = new Font("SansSerif", Font.PLAIN, 14);
    private static final Stroke BOX_STROKE = new BasicStroke(2.2f);
    private static final Stroke LINE_STROKE = new BasicStroke(2.1f);
    private static final Stroke DASHED_STROKE = new BasicStroke(
        2.0f, BasicStroke.CAP_BUTT, BasicStroke.JOIN_ROUND, 0, new float[]{10f, 8f}, 0
    );

    public static void main(String[] args) throws Exception {
        Path outputDir = Paths.get("POS", "generated-diagrams");
        Files.createDirectories(outputDir);

        Path context = outputDir.resolve("context_dfd.png");
        Path level1 = outputDir.resolve("level1_dfd.png");
        Path useCase = outputDir.resolve("use_case_diagram.png");
        Path sheet = outputDir.resolve("all_diagrams_sheet.png");

        BufferedImage contextImage = drawContextDfd();
        BufferedImage level1Image = drawLevel1Dfd();
        BufferedImage useCaseImage = drawUseCaseDiagram();

        ImageIO.write(contextImage, "png", context.toFile());
        ImageIO.write(level1Image, "png", level1.toFile());
        ImageIO.write(useCaseImage, "png", useCase.toFile());
        ImageIO.write(buildSheet(contextImage, level1Image, useCaseImage), "png", sheet.toFile());

        System.out.println("Generated:");
        System.out.println(context.toAbsolutePath());
        System.out.println(level1.toAbsolutePath());
        System.out.println(useCase.toAbsolutePath());
        System.out.println(sheet.toAbsolutePath());
    }

    private static BufferedImage drawContextDfd() {
        int width = 2200;
        int height = 1300;
        BufferedImage image = canvas(width, height);
        Graphics2D g = graphics(image);

        drawHeader(
            g, width,
            "Context-Level Data Flow Diagram",
            "Point-of-Sale and Inventory Management System for Maramag Fish Landing"
        );

        drawEntityBox(g, 120, 430, 360, 170, "Broker User");
        drawEntityBox(g, 1720, 430, 360, 170, "Administrative User\n(Admin/Staff)");
        drawProcess(g, 740, 340, 720, 360,
            "Point-of-Sale and Inventory\nManagement System\nfor Maramag Fish Landing"
        );

        drawArrow(g, 480, 500, 740, 500);
        drawArrow(g, 1460, 560, 1720, 560);
        drawArrow(g, 1720, 470, 1460, 470);
        drawArrow(g, 740, 590, 480, 590);

        drawLabel(g, 535, 370, 350, 110,
            "Login, profile updates,\nfish types, fish prices,\nfish boxes, QR scan,\nsales, buyer, payments,\nreturn/missing requests"
        );
        drawLabel(g, 1310, 350, 380, 100,
            "Login, profile updates,\nuser account data,\nactivation/deactivation,\nmonitoring and report queries"
        );
        drawLabel(g, 500, 645, 360, 110,
            "Authentication result,\nprofile details,\nfish box details,\nQR lookup result,\nsales, balances, receipt,\ndashboard and analytics"
        );
        drawLabel(g, 1310, 640, 370, 95,
            "Authentication result,\nprofile details,\nuser records,\ndashboard summaries,\nsales analysis,\ntracking reports"
        );

        drawFooterNote(g, 90, 1110,
            "External entities: Broker User and Administrative User (Admin/Staff)"
        );
        drawFooterNote(g, 90, 1145,
            "Main process: Point-of-Sale and Inventory Management System for Maramag Fish Landing"
        );

        g.dispose();
        return image;
    }

    private static BufferedImage drawLevel1Dfd() {
        int width = 2800;
        int height = 1800;
        BufferedImage image = canvas(width, height);
        Graphics2D g = graphics(image);

        drawHeader(
            g, width,
            "Level 1 Data Flow Diagram",
            "Exact processes and data stores aligned with the implemented Laravel system"
        );

        drawEntityBox(g, 70, 640, 320, 120, "Broker User");
        drawEntityBox(g, 2410, 640, 320, 120, "Administrative User\n(Admin/Staff)");

        drawProcess(g, 560, 200, 460, 120, "1.0 Authenticate and\nManage Profile");
        drawProcess(g, 1090, 200, 460, 120, "7.0 Manage User\nAccounts");

        drawProcess(g, 420, 470, 460, 120, "2.0 Manage Fish Types\nand Fish Prices");
        drawProcess(g, 940, 470, 460, 120, "3.0 Manage Fish Boxes\nand QR Tracking");
        drawProcess(g, 1460, 470, 460, 120, "4.0 Process Sales\nTransactions");

        drawProcess(g, 700, 760, 460, 120, "5.0 Process Payments\nand Print Receipts");
        drawProcess(g, 1260, 760, 460, 120, "6.0 Generate Reports\nand Monitoring Output");

        drawDataStore(g, 260, 1180, 420, 140, "D1 User and Role Records\nusers, roles, user_roles,\nemployees, brokers");
        drawDataStore(g, 760, 1180, 280, 140, "D2 Buyer Records\nbuyers");
        drawDataStore(g, 1120, 1180, 360, 140, "D3 Fish Type and Price Records\nfish_types, broker_fish_type,\nfish_prices");
        drawDataStore(g, 1560, 1180, 430, 140, "D4 Fish Box and Inventory Records\nfish_boxes, fish_box_purchases,\nfish_inventory");
        drawDataStore(g, 2070, 1180, 380, 140, "D5 Sales and Payment Records\nsales, sales_details,\npayments");

        drawArrow(g, 390, 700, 560, 260);
        drawArrow(g, 390, 700, 420, 530);
        drawArrow(g, 390, 700, 940, 530);
        drawArrow(g, 390, 700, 1460, 530);
        drawArrow(g, 390, 700, 700, 820);
        drawArrow(g, 390, 700, 1260, 820);

        drawArrow(g, 2410, 700, 1550, 260);
        drawArrow(g, 2410, 700, 1260, 820);
        drawArrow(g, 2410, 700, 1550, 530);

        drawArrow(g, 790, 320, 470, 1180);
        drawArrow(g, 1320, 320, 470, 1180);

        drawArrow(g, 650, 590, 1300, 1180);
        drawArrow(g, 1170, 590, 1300, 1180);
        drawArrow(g, 1170, 590, 1775, 1180);
        drawArrow(g, 1690, 590, 900, 1180);
        drawArrow(g, 1690, 590, 1775, 1180);
        drawArrow(g, 1690, 590, 2260, 1180);
        drawArrow(g, 930, 880, 2260, 1180);
        drawArrow(g, 1490, 880, 470, 1180);
        drawArrow(g, 1490, 880, 1300, 1180);
        drawArrow(g, 1490, 880, 1775, 1180);
        drawArrow(g, 1490, 880, 2260, 1180);

        drawArrow(g, 1020, 260, 390, 640);
        drawArrow(g, 880, 530, 390, 700);
        drawArrow(g, 1400, 530, 390, 700);
        drawArrow(g, 1160, 820, 390, 700);
        drawArrow(g, 1720, 820, 390, 700);

        drawArrow(g, 1550, 260, 2410, 640);
        drawArrow(g, 1720, 820, 2410, 700);

        drawLabel(g, 110, 470, 250, 84, "credentials,\nprofile updates,\noperational data");
        drawLabel(g, 2370, 470, 280, 84, "credentials,\nuser management,\nreport requests");
        drawLabel(g, 1910, 840, 270, 74, "dashboard,\nanalysis,\ntracking output");
        drawLabel(g, 430, 860, 260, 80, "sales result,\npayment result,\nreceipt and reports");

        drawFooterNote(g, 85, 1515, "Processes: 1.0 authentication, 2.0 fish types and prices, 3.0 fish boxes and QR, 4.0 sales, 5.0 payments, 6.0 reports, 7.0 user accounts");
        drawFooterNote(g, 85, 1550, "Data stores: D1 users and roles, D2 buyers, D3 fish types and prices, D4 fish boxes and inventory, D5 sales and payments");

        g.dispose();
        return image;
    }

    private static BufferedImage drawUseCaseDiagram() {
        int width = 2800;
        int height = 1900;
        BufferedImage image = canvas(width, height);
        Graphics2D g = graphics(image);

        drawHeader(
            g, width,
            "Use Case Diagram",
            "Actors and functions supported by the implemented system"
        );

        drawStickFigure(g, 180, 430, "Broker User");
        drawStickFigure(g, 2470, 430, "Administrative User\n(Admin/Staff)");

        g.setColor(BOUNDARY);
        g.setStroke(BOX_STROKE);
        g.draw(new RoundRectangle2D.Double(520, 170, 1760, 1540, 28, 28));
        g.setFont(SHAPE_TITLE_FONT);
        g.setColor(TITLE);
        g.drawString("Point-of-Sale and Inventory Management System for Maramag Fish Landing", 620, 210);

        drawUseCase(g, 720, 290, 320, 95, "Log in");
        drawUseCase(g, 1120, 290, 320, 95, "Manage Profile");
        drawUseCase(g, 1520, 290, 320, 95, "View Broker Dashboard");

        drawUseCase(g, 700, 460, 360, 95, "View Sales Analytics");
        drawUseCase(g, 1130, 460, 300, 95, "Manage Fish Types");
        drawUseCase(g, 1490, 460, 360, 95, "Manage Fish Prices");

        drawUseCase(g, 700, 640, 360, 95, "Register Fish Boxes");
        drawUseCase(g, 1130, 640, 300, 95, "Update Fish Boxes");
        drawUseCase(g, 1490, 640, 360, 95, "Delete Fish Boxes");

        drawUseCase(g, 700, 820, 360, 110, "Scan QR Code for\nFish Box Lookup");
        drawUseCase(g, 1130, 820, 300, 110, "Return Fish Box\nvia QR");
        drawUseCase(g, 1490, 820, 360, 110, "Mark Fish Box\nas Missing");

        drawUseCase(g, 700, 1030, 360, 95, "Create Sales Transaction");
        drawUseCase(g, 1130, 1030, 300, 95, "Update Sales Transaction");
        drawUseCase(g, 1490, 1030, 360, 95, "Delete Sales Transaction");

        drawUseCase(g, 700, 1200, 360, 95, "Record Buyer Information");
        drawUseCase(g, 1130, 1200, 300, 95, "Record Payment");
        drawUseCase(g, 1490, 1200, 360, 95, "Print Receipt");

        drawUseCase(g, 850, 1390, 340, 95, "View Admin Dashboard");
        drawUseCase(g, 1260, 1390, 280, 95, "Manage Users");
        drawUseCase(g, 1600, 1390, 420, 95, "View Broker Sales Analysis");
        drawUseCase(g, 1130, 1540, 620, 95, "View Fish Box Tracking History");

        connectActorToUseCase(g, 290, 430, 720, 338);
        connectActorToUseCase(g, 290, 430, 1120, 338);
        connectActorToUseCase(g, 290, 430, 1520, 338);
        connectActorToUseCase(g, 290, 430, 700, 507);
        connectActorToUseCase(g, 290, 430, 1130, 507);
        connectActorToUseCase(g, 290, 430, 1490, 507);
        connectActorToUseCase(g, 290, 430, 700, 688);
        connectActorToUseCase(g, 290, 430, 1130, 688);
        connectActorToUseCase(g, 290, 430, 1490, 688);
        connectActorToUseCase(g, 290, 430, 700, 875);
        connectActorToUseCase(g, 290, 430, 1130, 875);
        connectActorToUseCase(g, 290, 430, 1490, 875);
        connectActorToUseCase(g, 290, 430, 700, 1078);
        connectActorToUseCase(g, 290, 430, 1130, 1078);
        connectActorToUseCase(g, 290, 430, 1490, 1078);
        connectActorToUseCase(g, 290, 430, 700, 1248);
        connectActorToUseCase(g, 290, 430, 1130, 1248);
        connectActorToUseCase(g, 290, 430, 1490, 1248);

        connectActorToUseCase(g, 2520, 430, 850, 1438);
        connectActorToUseCase(g, 2520, 430, 1260, 1438);
        connectActorToUseCase(g, 2520, 430, 1600, 1438);
        connectActorToUseCase(g, 2520, 430, 1130, 1588);
        connectActorToUseCase(g, 2520, 430, 720, 338);
        connectActorToUseCase(g, 2520, 430, 1120, 338);

        drawInclude(g, 880, 1125, 880, 1200, "<<include>>");
        drawInclude(g, 930, 1030, 880, 930, "<<include>>");
        drawInclude(g, 1260, 1125, 1670, 1200, "<<include>>");
        drawExtend(g, 1280, 820, 1030, 820, "<<extend>>");

        drawFooterNote(g, 90, 1790, "Broker user functions include fish types, fish prices, fish boxes, QR lookup, sales, payments, receipt printing, and analytics");
        drawFooterNote(g, 90, 1825, "Administrative user functions include dashboard monitoring, user management, broker sales analysis, and fish box tracking history");

        g.dispose();
        return image;
    }

    private static BufferedImage buildSheet(BufferedImage context, BufferedImage level1, BufferedImage useCase) {
        int width = 2900;
        int gap = 70;
        int height = 140 + context.getHeight() + gap + level1.getHeight() + gap + useCase.getHeight() + 120;
        BufferedImage sheet = canvas(width, height);
        Graphics2D g = graphics(sheet);

        g.setColor(TITLE);
        g.setFont(TITLE_FONT);
        g.drawString("Capstone Diagram PNG Export", 90, 80);
        g.setColor(SUBTITLE);
        g.setFont(SUBTITLE_FONT);
        g.drawString("Context DFD, Level 1 DFD, and Use Case Diagram", 90, 112);

        int x = (width - context.getWidth()) / 2;
        int y = 150;
        g.drawImage(context, x, y, null);
        y += context.getHeight() + gap;
        x = (width - level1.getWidth()) / 2;
        g.drawImage(level1, x, y, null);
        y += level1.getHeight() + gap;
        x = (width - useCase.getWidth()) / 2;
        g.drawImage(useCase, x, y, null);

        g.dispose();
        return sheet;
    }

    private static BufferedImage canvas(int width, int height) {
        BufferedImage image = new BufferedImage(width, height, BufferedImage.TYPE_INT_ARGB);
        Graphics2D g = image.createGraphics();
        g.setPaint(new GradientPaint(0, 0, Color.WHITE, 0, height, BG));
        g.fillRect(0, 0, width, height);
        g.dispose();
        return image;
    }

    private static Graphics2D graphics(BufferedImage image) {
        Graphics2D g = image.createGraphics();
        g.setRenderingHint(RenderingHints.KEY_ANTIALIASING, RenderingHints.VALUE_ANTIALIAS_ON);
        g.setRenderingHint(RenderingHints.KEY_TEXT_ANTIALIASING, RenderingHints.VALUE_TEXT_ANTIALIAS_ON);
        g.setRenderingHint(RenderingHints.KEY_RENDERING, RenderingHints.VALUE_RENDER_QUALITY);
        return g;
    }

    private static void drawHeader(Graphics2D g, int width, String title, String subtitle) {
        g.setColor(TITLE);
        g.setFont(TITLE_FONT);
        int titleWidth = g.getFontMetrics().stringWidth(title);
        g.drawString(title, (width - titleWidth) / 2, 65);

        g.setColor(SUBTITLE);
        g.setFont(SUBTITLE_FONT);
        int subWidth = g.getFontMetrics().stringWidth(subtitle);
        g.drawString(subtitle, (width - subWidth) / 2, 102);
    }

    private static void drawEntityBox(Graphics2D g, int x, int y, int w, int h, String text) {
        g.setColor(ENTITY_FILL);
        g.fillRoundRect(x, y, w, h, 24, 24);
        g.setColor(ENTITY_BORDER);
        g.setStroke(BOX_STROKE);
        g.drawRoundRect(x, y, w, h, 24, 24);
        drawCenteredWrappedText(g, text, x + 20, y + 20, w - 40, h - 40, SHAPE_TITLE_FONT, TITLE);
    }

    private static void drawProcess(Graphics2D g, int x, int y, int w, int h, String text) {
        g.setColor(PROCESS_FILL);
        g.fillRoundRect(x, y, w, h, 40, 40);
        g.setColor(PROCESS_BORDER);
        g.setStroke(BOX_STROKE);
        g.drawRoundRect(x, y, w, h, 40, 40);
        drawCenteredWrappedText(g, text, x + 20, y + 18, w - 40, h - 36, SHAPE_TITLE_FONT, TITLE);
    }

    private static void drawDataStore(Graphics2D g, int x, int y, int w, int h, String text) {
        g.setColor(STORE_FILL);
        g.fillRoundRect(x, y, w, h, 18, 18);
        g.setColor(STORE_BORDER);
        g.setStroke(BOX_STROKE);
        g.drawRoundRect(x, y, w, h, 18, 18);
        g.drawLine(x + 14, y, x + 14, y + h);
        g.drawLine(x + w - 14, y, x + w - 14, y + h);
        drawCenteredWrappedText(g, text, x + 20, y + 18, w - 40, h - 36, SHAPE_TEXT_FONT, TITLE);
    }

    private static void drawUseCase(Graphics2D g, int x, int y, int w, int h, String text) {
        g.setColor(USECASE_FILL);
        g.fillOval(x, y, w, h);
        g.setColor(USECASE_BORDER);
        g.setStroke(BOX_STROKE);
        g.drawOval(x, y, w, h);
        drawCenteredWrappedText(g, text, x + 24, y + 18, w - 48, h - 36, SHAPE_TEXT_FONT, TITLE);
    }

    private static void drawStickFigure(Graphics2D g, int centerX, int topY, String label) {
        g.setColor(TITLE);
        g.setStroke(new BasicStroke(3f));
        g.drawOval(centerX - 22, topY, 44, 44);
        g.drawLine(centerX, topY + 44, centerX, topY + 110);
        g.drawLine(centerX - 42, topY + 72, centerX + 42, topY + 72);
        g.drawLine(centerX, topY + 110, centerX - 36, topY + 165);
        g.drawLine(centerX, topY + 110, centerX + 36, topY + 165);
        drawCenteredWrappedText(g, label, centerX - 110, topY + 182, 220, 64, SHAPE_TEXT_FONT, TITLE);
    }

    private static void connectActorToUseCase(Graphics2D g, int actorX, int actorY, int useCaseX, int useCaseY) {
        g.setColor(ARROW);
        g.setStroke(LINE_STROKE);
        g.drawLine(actorX, actorY, useCaseX, useCaseY);
    }

    private static void drawArrow(Graphics2D g, int x1, int y1, int x2, int y2) {
        g.setColor(ARROW);
        g.setStroke(LINE_STROKE);
        g.drawLine(x1, y1, x2, y2);
        drawArrowHead(g, x1, y1, x2, y2);
    }

    private static void drawArrowHead(Graphics2D g, int x1, int y1, int x2, int y2) {
        double angle = Math.atan2(y2 - y1, x2 - x1);
        int size = 11;
        int xA = (int) (x2 - size * Math.cos(angle - Math.PI / 7));
        int yA = (int) (y2 - size * Math.sin(angle - Math.PI / 7));
        int xB = (int) (x2 - size * Math.cos(angle + Math.PI / 7));
        int yB = (int) (y2 - size * Math.sin(angle + Math.PI / 7));
        Polygon head = new Polygon();
        head.addPoint(x2, y2);
        head.addPoint(xA, yA);
        head.addPoint(xB, yB);
        g.fillPolygon(head);
    }

    private static void drawInclude(Graphics2D g, int x1, int y1, int x2, int y2, String label) {
        g.setColor(ARROW);
        g.setStroke(DASHED_STROKE);
        g.drawLine(x1, y1, x2, y2);
        drawArrowHead(g, x1, y1, x2, y2);
        drawLabel(g, Math.min(x1, x2) - 20, ((y1 + y2) / 2) - 28, 140, 42, label);
    }

    private static void drawExtend(Graphics2D g, int x1, int y1, int x2, int y2, String label) {
        g.setColor(ARROW);
        g.setStroke(DASHED_STROKE);
        g.drawLine(x1, y1, x2, y2);
        drawArrowHead(g, x1, y1, x2, y2);
        drawLabel(g, ((x1 + x2) / 2) - 70, y1 - 60, 140, 42, label);
    }

    private static void drawLabel(Graphics2D g, int x, int y, int w, int h, String text) {
        g.setColor(LABEL_BG);
        g.fillRoundRect(x, y, w, h, 18, 18);
        g.setColor(new Color(203, 213, 225));
        g.setStroke(new BasicStroke(1.2f));
        g.drawRoundRect(x, y, w, h, 18, 18);
        drawCenteredWrappedText(g, text, x + 12, y + 10, w - 24, h - 18, LABEL_FONT, TITLE);
    }

    private static void drawFooterNote(Graphics2D g, int x, int y, String text) {
        g.setColor(SUBTITLE);
        g.setFont(SMALL_FONT);
        g.drawString(text, x, y);
    }

    private static void drawCenteredWrappedText(
        Graphics2D g,
        String text,
        int x,
        int y,
        int width,
        int height,
        Font font,
        Color color
    ) {
        g.setFont(font);
        g.setColor(color);
        FontMetrics metrics = g.getFontMetrics(font);
        List<String> lines = wrap(text, metrics, width);
        int lineHeight = metrics.getHeight();
        int totalHeight = lines.size() * lineHeight;
        int currentY = y + Math.max(0, (height - totalHeight) / 2) + metrics.getAscent();

        for (String line : lines) {
            int lineWidth = metrics.stringWidth(line);
            int currentX = x + Math.max(0, (width - lineWidth) / 2);
            g.drawString(line, currentX, currentY);
            currentY += lineHeight;
        }
    }

    private static List<String> wrap(String text, FontMetrics metrics, int maxWidth) {
        List<String> lines = new ArrayList<>();
        for (String rawLine : text.split("\\n")) {
            String[] words = rawLine.trim().split("\\s+");
            if (rawLine.trim().isEmpty()) {
                lines.add("");
                continue;
            }

            StringBuilder current = new StringBuilder();
            for (String word : words) {
                String candidate = current.length() == 0 ? word : current + " " + word;
                if (metrics.stringWidth(candidate) <= maxWidth) {
                    current.setLength(0);
                    current.append(candidate);
                } else {
                    if (current.length() > 0) {
                        lines.add(current.toString());
                    }
                    current.setLength(0);
                    current.append(word);
                }
            }
            if (current.length() > 0) {
                lines.add(current.toString());
            }
        }
        return lines;
    }
}

<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet 
xmlns:fo="http://www.w3.org/1999/XSL/Format" 
xmlns:xsl="http://www.w3.org/1999/XSL/Transform" 
version="1.0"
xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0" 
xmlns:style="urn:oasis:names:tc:opendocument:xmlns:style:1.0" 
xmlns:text="urn:oasis:names:tc:opendocument:xmlns:text:1.0" 
xmlns:table="urn:oasis:names:tc:opendocument:xmlns:table:1.0" 
xmlns:draw="urn:oasis:names:tc:opendocument:xmlns:drawing:1.0"
xmlns:xlink="http://www.w3.org/1999/xlink" 
xmlns:dc="http://purl.org/dc/elements/1.1/" 
xmlns:meta="urn:oasis:names:tc:opendocument:xmlns:meta:1.0" 
xmlns:number="urn:oasis:names:tc:opendocument:xmlns:datastyle:1.0" 
xmlns:svg="urn:oasis:names:tc:opendocument:xmlns:svg-compatible:1.0" 
xmlns:form="urn:oasis:names:tc:opendocument:xmlns:form:1.0" 
>

<xsl:output method="xml" version="1.0" indent="yes"/>
<xsl:template match="abschlusspruefung">
<office:document-content 
xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0" 
xmlns:style="urn:oasis:names:tc:opendocument:xmlns:style:1.0" 
xmlns:text="urn:oasis:names:tc:opendocument:xmlns:text:1.0" 
xmlns:table="urn:oasis:names:tc:opendocument:xmlns:table:1.0" 
xmlns:draw="urn:oasis:names:tc:opendocument:xmlns:drawing:1.0" 
xmlns:fo="urn:oasis:names:tc:opendocument:xmlns:xsl-fo-compatible:1.0" 
xmlns:xlink="http://www.w3.org/1999/xlink" 
xmlns:dc="http://purl.org/dc/elements/1.1/" 
xmlns:meta="urn:oasis:names:tc:opendocument:xmlns:meta:1.0" 
xmlns:number="urn:oasis:names:tc:opendocument:xmlns:datastyle:1.0" 
xmlns:svg="urn:oasis:names:tc:opendocument:xmlns:svg-compatible:1.0" 
xmlns:chart="urn:oasis:names:tc:opendocument:xmlns:chart:1.0" 
xmlns:dr3d="urn:oasis:names:tc:opendocument:xmlns:dr3d:1.0" 
xmlns:math="http://www.w3.org/1998/Math/MathML" 
xmlns:form="urn:oasis:names:tc:opendocument:xmlns:form:1.0" 
xmlns:script="urn:oasis:names:tc:opendocument:xmlns:script:1.0" 
xmlns:ooo="http://openoffice.org/2004/office" 
xmlns:ooow="http://openoffice.org/2004/writer" 
xmlns:oooc="http://openoffice.org/2004/calc" 
xmlns:dom="http://www.w3.org/2001/xml-events" 
xmlns:xforms="http://www.w3.org/2002/xforms" 
xmlns:xsd="http://www.w3.org/2001/XMLSchema" 
xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" 
xmlns:rpt="http://openoffice.org/2005/report" 
xmlns:of="urn:oasis:names:tc:opendocument:xmlns:of:1.2" 
xmlns:xhtml="http://www.w3.org/1999/xhtml" 
xmlns:grddl="http://www.w3.org/2003/g/data-view#" 
xmlns:officeooo="http://openoffice.org/2009/office" 
xmlns:tableooo="http://openoffice.org/2009/table" 
xmlns:drawooo="http://openoffice.org/2010/draw" 
xmlns:calcext="urn:org:documentfoundation:names:experimental:calc:xmlns:calcext:1.0" 
xmlns:loext="urn:org:documentfoundation:names:experimental:office:xmlns:loext:1.0" 
xmlns:field="urn:openoffice:names:experimental:ooo-ms-interop:xmlns:field:1.0" 
xmlns:formx="urn:openoffice:names:experimental:ooxml-odf-interop:xmlns:form:1.0" 
xmlns:css3t="http://www.w3.org/TR/css3-text/" 
office:version="1.2">
	<office:scripts/>
	<office:font-face-decls>
		<style:font-face style:name="Mangal1" svg:font-family="Mangal"/>
		<style:font-face style:name="Liberation Serif" svg:font-family="&apos;Liberation Serif&apos;" style:font-family-generic="roman" style:font-pitch="variable"/>
		<style:font-face style:name="Arial" svg:font-family="Arial" style:font-family-generic="swiss" style:font-pitch="variable"/>
		<style:font-face style:name="Liberation Sans" svg:font-family="&apos;Liberation Sans&apos;" style:font-family-generic="swiss" style:font-pitch="variable"/>
		<style:font-face style:name="Mangal" svg:font-family="Mangal" style:font-family-generic="system" style:font-pitch="variable"/>
		<style:font-face style:name="Microsoft YaHei" svg:font-family="&apos;Microsoft YaHei&apos;" style:font-family-generic="system" style:font-pitch="variable"/>
		<style:font-face style:name="SimSun" svg:font-family="SimSun" style:font-family-generic="system" style:font-pitch="variable"/>
	</office:font-face-decls>
	<office:automatic-styles>
		<style:style style:name="P1" style:family="paragraph" style:parent-style-name="Standard">
			<style:paragraph-properties fo:margin-left="0cm" fo:margin-right="0cm" fo:line-height="120%" fo:text-align="center" style:justify-single-word="false" fo:text-indent="0cm" style:auto-text-indent="false" style:writing-mode="page"/>
			<style:text-properties style:font-name="Arial" fo:font-size="28pt" style:font-size-asian="28pt" style:font-size-complex="28pt"/>
		</style:style>
		<style:style style:name="P2" style:family="paragraph" style:parent-style-name="Standard">
			<style:paragraph-properties fo:margin-left="0cm" fo:margin-right="0cm" fo:line-height="120%" fo:text-indent="0cm" style:auto-text-indent="false" style:writing-mode="page"/>
			<style:text-properties style:font-name="Arial" fo:font-size="10pt" style:font-size-asian="10pt" style:font-size-complex="10pt"/>
		</style:style>
		<style:style style:name="P3" style:family="paragraph" style:parent-style-name="Standard">
			<style:paragraph-properties fo:margin-left="0cm" fo:margin-right="0cm" fo:line-height="120%" fo:text-align="center" style:justify-single-word="false" fo:text-indent="0cm" style:auto-text-indent="false" style:writing-mode="page"/>
			<style:text-properties style:font-name="Arial" fo:font-size="10pt" style:font-size-asian="10pt" style:font-size-complex="10pt"/>
		</style:style>
		<style:style style:name="P4" style:family="paragraph" style:parent-style-name="Standard">
			<style:paragraph-properties fo:margin-left="0cm" fo:margin-right="0cm" fo:line-height="120%" fo:text-align="justify" style:justify-single-word="false" fo:text-indent="0cm" style:auto-text-indent="false" style:writing-mode="page"/>
			<style:text-properties style:font-name="Arial" fo:font-size="10pt" style:font-size-asian="10pt" style:font-size-complex="10pt"/>
		</style:style>
		<style:style style:name="P5" style:family="paragraph" style:parent-style-name="Standard">
			<style:paragraph-properties fo:margin-left="0cm" fo:margin-right="0cm" fo:line-height="120%" fo:text-align="center" style:justify-single-word="false" fo:text-indent="0cm" style:auto-text-indent="false" style:writing-mode="page"/>
			<style:text-properties style:font-name="Arial" fo:font-size="16pt" style:font-size-asian="16pt" style:font-size-complex="16pt"/>
		</style:style>
		<style:style style:name="P6" style:family="paragraph" style:parent-style-name="Standard">
			<style:paragraph-properties fo:margin-left="0cm" fo:margin-right="0cm" fo:line-height="120%" fo:text-align="justify" style:justify-single-word="false" fo:text-indent="0cm" style:auto-text-indent="false" fo:break-before="column" style:writing-mode="page"/>
			<style:text-properties style:font-name="Arial" fo:font-size="10pt" style:font-size-asian="10pt" style:font-size-complex="10pt"/>
		</style:style>
		<style:style style:name="P7" style:family="paragraph" style:parent-style-name="Header">
			<style:paragraph-properties fo:line-height="120%" style:writing-mode="page"/>
			<style:text-properties style:font-name="Arial" fo:font-size="10pt" fo:font-weight="bold" style:font-size-asian="10pt" style:font-weight-asian="bold" style:font-size-complex="10pt" style:font-weight-complex="bold"/>
		</style:style>
		<style:style style:name="P8" style:family="paragraph" style:parent-style-name="Header">
			<style:paragraph-properties fo:line-height="120%" style:writing-mode="page"/>
			<style:text-properties style:font-name="Arial" fo:font-size="10pt" style:font-size-asian="10pt" style:font-size-complex="10pt"/>
		</style:style>
		<style:style style:name="Seitenumbruch" style:family="paragraph" style:parent-style-name="Standard">
			<style:paragraph-properties fo:break-before="page" fo:margin-left="0cm" fo:margin-right="0cm" fo:line-height="120%" fo:text-align="center" style:justify-single-word="false" fo:text-indent="0cm" style:auto-text-indent="false" style:writing-mode="page"/>
			<style:text-properties style:font-name="Arial" fo:font-size="28pt" style:font-size-asian="28pt" style:font-size-complex="28pt"/>
		</style:style>
		<style:style style:name="Warning" style:family="paragraph" style:parent-style-name="Standard">
			<style:paragraph-properties fo:line-height="122%" fo:text-align="center" style:justify-single-word="false"/>
			<style:text-properties style:font-name="Arial" fo:color="#ff3333" fo:font-weight="bold" fo:font-size="16pt" style:font-size-asian="16pt" style:font-size-complex="16pt"/>
		</style:style>
		<style:style style:name="fr1" style:family="graphic" style:parent-style-name="Graphics">
			<style:graphic-properties fo:margin-left="0.319cm" fo:margin-right="0.319cm" style:run-through="background" style:wrap="run-through" style:number-wrapped-paragraphs="no-limit" style:vertical-pos="from-top" style:vertical-rel="paragraph" style:horizontal-pos="from-left" style:horizontal-rel="paragraph" fo:padding="0.026cm" fo:border="none" style:mirror="none" fo:clip="rect(0cm, 0cm, 0cm, 0cm)" draw:luminance="0%" draw:contrast="0%" draw:red="0%" draw:green="0%" draw:blue="0%" draw:gamma="100%" draw:color-inversion="false" draw:image-opacity="100%" draw:color-mode="standard"/>
		</style:style>
		<style:style style:name="fr2" style:family="graphic" style:parent-style-name="Graphics">
			<style:graphic-properties style:run-through="background" style:wrap="run-through" style:number-wrapped-paragraphs="no-limit" style:vertical-pos="from-top" style:vertical-rel="page" style:horizontal-pos="center" style:horizontal-rel="page" style:mirror="none" fo:clip="rect(0cm, 0cm, 0cm, 0cm)" draw:luminance="0%" draw:contrast="0%" draw:red="0%" draw:green="0%" draw:blue="0%" draw:gamma="100%" draw:color-inversion="false" draw:image-opacity="100%" draw:color-mode="standard"/>
		</style:style>
		<style:style style:name="Sect1" style:family="section">
			<style:section-properties text:dont-balance-text-columns="true" style:editable="false">
				<style:columns fo:column-count="2" fo:column-gap="1.199cm">
					<style:column-sep style:width="0.009cm" style:color="#000000" style:height="100%" style:style="solid"/>
					<style:column style:rel-width="4819*" fo:start-indent="0cm" fo:end-indent="0.6cm"/>
					<style:column style:rel-width="4819*" fo:start-indent="0.6cm" fo:end-indent="0cm"/>
				</style:columns>
			</style:section-properties>
		</style:style>
	</office:automatic-styles>
	
	<office:body>
		<xsl:apply-templates select="pruefung"/>
	</office:body>
	</office:document-content>
</xsl:template>

<xsl:template match="pruefung">
	<!-- 
	Der Bescheid wird nur aufgrund der zuletzt vorhandenen Abschlusspruefung ausgestellt
	Diese wird als erstes vom RDF geliefert
	-->
	<xsl:if test="position()=1">
		<office:text 
		text:use-soft-page-breaks="true" 
		xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0" 
		xmlns:style="urn:oasis:names:tc:opendocument:xmlns:style:1.0" 
		xmlns:text="urn:oasis:names:tc:opendocument:xmlns:text:1.0" 
		xmlns:table="urn:oasis:names:tc:opendocument:xmlns:table:1.0" 
		xmlns:draw="urn:oasis:names:tc:opendocument:xmlns:drawing:1.0" 
		xmlns:svg="urn:oasis:names:tc:opendocument:xmlns:svg-compatible:1.0">
				<text:sequence-decls 
				xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0" 
				xmlns:style="urn:oasis:names:tc:opendocument:xmlns:style:1.0" 
				xmlns:text="urn:oasis:names:tc:opendocument:xmlns:text:1.0" 
				xmlns:table="urn:oasis:names:tc:opendocument:xmlns:table:1.0" 
				xmlns:draw="urn:oasis:names:tc:opendocument:xmlns:drawing:1.0" 
				xmlns:svg="urn:oasis:names:tc:opendocument:xmlns:svg-compatible:1.0">
				<text:sequence-decl text:display-outline-level="0" text:name="Illustration"/>
				<text:sequence-decl text:display-outline-level="0" text:name="Table"/>
				<text:sequence-decl text:display-outline-level="0" text:name="Text"/>
				<text:sequence-decl text:display-outline-level="0" text:name="Drawing"/>
			</text:sequence-decls>
			
			<text:p text:style-name="Seitenumbruch">
				<draw:frame xmlns:svg="urn:oasis:names:tc:opendocument:xmlns:svg-compatible:1.0" xmlns:xlink="http://www.w3.org/1999/xlink" draw:style-name="fr2" draw:name="Bild2" text:anchor-type="paragraph" svg:y="8.819cm" svg:width="11.449cm" svg:height="12.61cm" draw:z-index="1">
					<draw:image xlink:href="Pictures/100000000000087900000955F5761520DAB70522.jpg" xlink:type="simple" xlink:show="embed" xlink:actuate="onLoad"/>
				</draw:frame>Bescheid</text:p>
			
				<!-- Ueberprueft ob benoetigte Datenfelder leer sind -->
				<xsl:if test="gebdatum = ''"><text:p text:style-name="Warning">Geburtsdatum fehlt</text:p></xsl:if>
				<xsl:if test="datum = ''"><text:p text:style-name="Warning">Datum der Abschlussprüfung nicht gesetzt</text:p></xsl:if>
				<xsl:if test="titel = ''"><text:p text:style-name="Warning">Kein akademischer Grad ausgewählt</text:p></xsl:if>
				<xsl:if test="geburtsnation = ''"><text:p text:style-name="Warning">Geburtsnation fehlt</text:p></xsl:if>
				<xsl:if test="rektor = ''"><text:p text:style-name="Warning">Name des Rektors fehlt</text:p></xsl:if>
				<xsl:if test="geburtsnation_engl = ''"><text:p text:style-name="Warning">Englische Geburtsnation fehlt</text:p></xsl:if>
				
			<text:p text:style-name="P3"/>
			<text:p text:style-name="P3">Das Kollegium der Fachhochschule Technikum Wien verleiht</text:p>
			<text:p text:style-name="P3"/>
			<text:p text:style-name="P5"><xsl:value-of select="anrede" /><xsl:text> </xsl:text><xsl:value-of select="name" /></text:p>
			<text:p text:style-name="P3"/>
			<text:section text:style-name="Sect1" text:name="Bereich1">
				<text:p text:style-name="P4">geboren am <xsl:value-of select="gebdatum" /> in 
			<xsl:if test="string-length(gebort)!=0">
				<xsl:value-of select="gebort" />
				<xsl:text>, </xsl:text>
			</xsl:if>
				<xsl:value-of select="geburtsnation" />
			<xsl:choose>
				<xsl:when test="contains(anrede, 'err')">
					<xsl:text> der</xsl:text>
				</xsl:when>
				<xsl:when test="contains(anrede, 'rau')">
					<xsl:text> die</xsl:text>
				</xsl:when>
				<xsl:otherwise>
					<xsl:text> die/der</xsl:text>
				</xsl:otherwise>
			</xsl:choose>
 			durch Ablegung der 
 			<xsl:choose>
				<xsl:when test="stg_art='b'">Bachelor</xsl:when>					
				<xsl:when test="stg_art='m'">Master</xsl:when>					
				<xsl:when test="stg_art='d'">Diplom</xsl:when>					
				<xsl:when test="stg_art='l'">Lehrgang</xsl:when>				
				<xsl:when test="stg_art='k'">Kurzstudium</xsl:when>								
			</xsl:choose>prüfung am <xsl:value-of select="datum" /> den 
			<xsl:choose>
				<xsl:when test="stg_art='b'">Bachelor</xsl:when>					
				<xsl:when test="stg_art='m'">Master</xsl:when>					
				<xsl:when test="stg_art='d'">Diplom</xsl:when>					
				<xsl:when test="stg_art='l'">Lehrgang</xsl:when>				
				<xsl:when test="stg_art='k'">Kurzstudium</xsl:when>								
			</xsl:choose>studiengang</text:p>
				<text:p text:style-name="P4"/>
				<text:p text:style-name="P4"/>
				<text:p text:style-name="P5"><xsl:value-of select="stg_bezeichnung" /></text:p>
				<text:p text:style-name="P3">(Studiengangskennzahl <xsl:value-of select="studiengang_kz" />)</text:p>
				<text:p text:style-name="P3"/>
				<text:p text:style-name="P3">ordnungsgemäß abgeschlossen hat,</text:p>
				<text:p text:style-name="P3">gemäß § 6 Abs 1 FHStG, BGBl. Nr. 340/1993, idgF, den akademischen Grad</text:p>
				
				
				<text:p text:style-name="P6">born on <xsl:value-of select="gebdatum" /> in 
				<xsl:if test="string-length(gebort)!=0">
					<xsl:value-of select="gebort" />
					<xsl:text>, </xsl:text>
				</xsl:if>
					<xsl:value-of select="geburtsnation_engl" />
				who, by taking the 
				<xsl:choose>
				<xsl:when test="stg_art='b'">Bachelor</xsl:when>					
				<xsl:when test="stg_art='m'">Master</xsl:when>					
				<xsl:when test="stg_art='d'">Diploma</xsl:when>					
				<xsl:when test="stg_art='l'">Course</xsl:when>				
				<xsl:when test="stg_art='k'">Short study</xsl:when>											
			</xsl:choose> examination on <xsl:value-of select="datum" />, has duly completed the 
			<xsl:choose>
				<xsl:when test="stg_art='b'">Bachelor</xsl:when>					
				<xsl:when test="stg_art='m'">Master</xsl:when>					
				<xsl:when test="stg_art='d'">Diploma</xsl:when>					
				<xsl:when test="stg_art='l'">Course</xsl:when>				
				<xsl:when test="stg_art='k'">Short study</xsl:when>											
			</xsl:choose><xsl:if test="stg_art != 'l' or 'k'" >'s</xsl:if> degree program</text:p>
				<text:p text:style-name="P4"/>
				<!-- Wenn Geburtsort oder Nation leer ist, wird im Englischen ein extra Zeilenumbruch ergaenzt -->
				<xsl:if test="string-length(gebort)=0 or string-length(geburtsnation)=0">
					<text:p text:style-name="P4"/>
				</xsl:if>
				<text:p text:style-name="P5"><xsl:value-of select="stg_bezeichnung_engl" /></text:p>
				<text:p text:style-name="P3">(Degree Program Code <xsl:value-of select="studiengang_kz" />)</text:p>
				<text:p text:style-name="P3"/>
				<text:p text:style-name="P3">according to § 6 Abs 1 FHStG, BGBl. No. 340/1993, as amended, the academic degree of</text:p>
				<text:p text:style-name="P4"/>
			</text:section>
			<text:p text:style-name="P3"/>
			<text:p text:style-name="P3"/>
			<text:p text:style-name="P5"><xsl:value-of select="titel" /> (<xsl:value-of select="akadgrad_kurzbz" />)</text:p>
			<text:p text:style-name="P3"/>
			<text:p text:style-name="P3"/>
			<text:section text:style-name="Sect1" text:name="Bereich2">
				<text:p text:style-name="P4">Rechtsmittelbelehrung: Gegen diesen Bescheid ist gemäß § 10 Abs 6 FHStG, BGBl. Nr. 340/1993, idgF, eine Beschwerde beim Bundesverwaltungsgericht zulässig. Sie ist innerhalb von vier Wochen ab Zustellung bei der belangten Behörde (Kollegium der Fachhochschule Technikum Wien) einzubringen.</text:p>
				<text:p text:style-name="P6">Appeal notice: An appeal against this decision may be lodged with the Federal Administrative Court (Bundesverwaltungsgericht) in accordance with Section 10 (6) of the FHStG, Federal Law Gazette no. 340/1993, as amended. It must be submitted to the relevant authority (Council of the University of Applied Sciences Technikum Wien) within four weeks of notification.</text:p>
			</text:section>
			<text:p text:style-name="P3"/>
			<xsl:choose>
				<xsl:when test="../signed">
				<text:p text:style-name="P3"/>
				<text:p text:style-name="P3">
					<draw:frame draw:style-name="fr3" draw:name="Bild1" text:anchor-type="paragraph" svg:width="17cm" svg:height="4.235cm" draw:z-index="0">
						<draw:image xlink:href="Pictures/Platzhalter_QR_FHC_GROSS_AMT_DE.png" xlink:type="simple" xlink:show="embed" xlink:actuate="onLoad"/>
					</draw:frame>
				</text:p>
				</xsl:when>
				<xsl:otherwise>
					<text:p text:style-name="P3"/>
					<text:p text:style-name="P3">Wien, <xsl:value-of select="ort_datum" /></text:p>
					<text:p text:style-name="P3"/>
					<text:p text:style-name="P3">Für das Fachhochschulkollegium</text:p>
					<text:p text:style-name="P3">Der Rektor</text:p>
					<text:p text:style-name="P3"/>
					<text:p text:style-name="P3"/>
					<text:p text:style-name="P3"/>
					<text:p text:style-name="P3"><xsl:value-of select="rektor" /></text:p>
				</xsl:otherwise>
			</xsl:choose>
		</office:text>
	</xsl:if>
</xsl:template>
</xsl:stylesheet>
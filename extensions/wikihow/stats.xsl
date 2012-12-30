<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" 
    xmlns:dc="http://purl.org/dc/elements/1.1/"
    xmlns:atom="http://purl.org/atom/ns#"
    version="1.0">

    <xsl:template match="/">
    <html xmlns="http://www.w3.org/1999/xhtml">
        <head>
            <title><xsl:value-of select="root/title"/></title>
            
            <style type="text/css">
                
                body {
                background-color: #DDD;
                }
                #branding {
                position: relative;
                top: 0px;
                left: 0px;
                width: 570px;
                background-color: #CDEB8B;
                color: #000000;
                text-align: left;
                padding: 15px;
                }
                
                #pageTitle {
                font-size: 40px;
                }
                
                #tagLine {
                font-size: 15px;
                }
                
                #wrapper {
                width: 600px;
                margin: 0px auto 0px auto;
                background-color: #FFF;
                padding: 0px;
                font-family: "Arial", "Verdana", helvetica, sans-serif;
                
                }
                
                .boxContent {
                font-size: .8em;
                }
                .feedSummary {
				padding: 10px;
                }
                
                
                .boxHeadline {
                font-size: 2em;
                }
                #descriptionBox {
                margin: 10px;
                border: 1px #000 solid;
                background-color: #F9F7ED;
                padding: 10px;
                }
                
                
                #subscribeBox {
                margin: 10px;
                border: 1px #000 solid;
                background-color: #F9F7ED;
                padding: 10px;
                }
                
                #subscribeHeadline {
                }
                
                #subscribeText {
                }
                
                
                #feedContent {
                margin: 10px;
                border: 1px #000 solid;
                background-color: #F5F5F5;
                padding: 10px;
                }
                
                .subImage {
                    vertical-align: middle;
                    border: 0px none;
					padding-right: 15px;
                }
                
            </style>
            
        </head>
        <body>
            
            
            <div id="wrapper">
               
	<!-- 
                <div id="branding">
                </div>
       -->         
                
                
                <div id="descriptionBox">
                    <h3><xsl:value-of select="root/title"/></h3>
                </div>
                
                
                
                <div id="feedContent">
                    
                    <!-- feed content goes here -->
                    <div class="boxHeadline" id="feedHeadline">
						Stats for <xsl:value-of select="root/lastBuildDate"/>
                    </div>
                   
                    <div class="boxContent" id="feedItems">
                       
					<h3>Overall stats</h3>
						<table width='100%'>
							<tr><td><b># of Page Views</b></td><td>  <xsl:value-of select="root/stats/total_views"/></td></tr>	
							<tr><td><b># of Edits</b></td><td><xsl:value-of select="root/stats/total_edits"/> </td></tr>	
							<tr><td><b># of New Good Articles</b></td><td> <xsl:value-of select="root/stats/good_articles"/></td></tr>	
							<tr><td><b># of Links E-mailed</b></td><td><xsl:value-of select="root/stats/links_emailed"/> </td></tr>	
							<tr><td><b># of New Pages</b></td><td><xsl:value-of select="root/stats/total_pages"/> </td></tr>	
							<tr><td><b># of New Admins</b></td><td><xsl:value-of select="root/stats/admins"/> </td></tr>	
							<tr><td><b># of New Users</b></td><td><xsl:value-of select="root/stats/users"/> </td></tr>	
							<tr><td><b># of New Images</b></td><td><xsl:value-of select="root/stats/images"/> </td></tr>	

						</table> 
					<h3>Top Pages </h3>
						<table> 
						<tr>
							<td>Page</td>
							<td># of views</td>
						</tr>
                        <xsl:for-each select="root/pageviews/page">
                                <tr>		
									<td><a href='{ url }'><xsl:value-of select="title"/></a></td>
									<td align='right'><xsl:value-of select="hits"/></td>
								</tr>
                        </xsl:for-each>
                       	</table> 

                    <h3>Users with most edits </h3>
                        <table width='100%'> 
                        <tr>
                            <td>User</td>
                            <td align='right'># of edits</td>
                            <td align='right'>% of total</td>
                        </tr>
                        <xsl:for-each select="root/editors/editor">
                         <tr><td><a href='{ url }'><xsl:value-of select="username"/></a></td> 
	                           <td align='right'><xsl:value-of select="edits"/></td>
	                           <td align='right'><xsl:value-of select="percent"/></td>
                         </tr>
                        </xsl:for-each>
                        </table> 


                    </div>
                    
                </div>
            </div>
            
        </body>
    </html>
    </xsl:template>
    
    
</xsl:stylesheet>
